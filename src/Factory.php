<?php
namespace WPAS;
use WPAS\Enum\FieldType;
use WPAS\Enum\RequestVar;
use WPAS\Enum\Relation;

class Factory extends StdObject
{

    private $args;
    private $form;
    private $request;
    private $fields;
    private $inputs;
    private $errors;
    private $wp_query_args;
    private $wp_query_obj;
    private $orderby_meta_keys;
    private $fields_ready;

    protected static $defaults = array(
        'wpas_id' => 'default',
        'form' => array(),
        'fields' => array(),
        'wp_query' => array(),
        'taxonomy_relation' => Relation::_AND,
        'meta_key_relation' => Relation::_AND,
        'debug' => false,
        'debug_level' => 'log'
    );

    protected static $rules = array(
        'form' => 'array',
        'fields' => 'array',
        'wp_query' => 'array',
        'taxonomy_relation' => 'Relation',
        'meta_key_relation' => 'Relation',
        'debug' => 'bool',
        'debug_level' => 'string'
    );

    public function __construct($args, $request = null) {
        $args = self::validate($args);
        $this->args = $this->preProcessArgs($args);
        $this->wp_query_args = $this->args['wp_query'];
        $this->errors = array();
        $this->inputs = array();
        $this->fields_ready = false;
        $this->request = $this->processRequest($request);
        $this->fields = $this->initFieldTable();
        $this->initFields();
        $this->buildForm();
    }

    /**
     * Creates empty table of fields as $field_type => FieldGroup pairs
     *
     * @return array
     */
    private function initFieldTable() {
        $table = array();
        $field_types = FieldType::getConstants();
        foreach ($field_types as $type) {
            $field_group = new FieldGroup();
            $field_group->setRelation($this->getFieldRelation($type));
            $table[$type] = $field_group;
        }
        return $table;
    }

    /**
     * Get relation for a given field type
     *
     * @param $field_type
     * @return string
     */
    private function getFieldRelation($field_type) {
        $include = array('meta_key' => 1, 'taxonomy' => 1);
        if (isset($include[$field_type])) return $this->args[$field_type.'_relation'];
        return Relation::_default;
    }

    /**
     * Perform initial processing of arguments
     *
     * @param $args
     * @return array
     */
    private function preProcessArgs($args) {
        global $post;

        if (!is_array($args)) return array();

        if (empty($args['form']['action']) && is_object($post) && isset($post->ID)) {
            $args['form']['action'] = get_permalink($post->ID);
        }

        $args = self::parseArgs($args, self::$defaults);

        return $args;
    }

    /**
     * Process and sanitize an array of request variables
     *
     * @param $request
     * @return mixed
     */
    private function processRequest($request) {
        $data = (empty($request)) ? $this->getRequestGlobal($this->args['form']) : $request;
        return new HttpRequest($data);
    }

    /**
     * Populate fields table
     */
    private function initFields() {
        if (empty($this->args['fields'])) return;
        foreach ($this->args['fields'] as $f) {
            try {
                $field = new Field($f);
            } catch (\Exception $e) {
                $this->addExceptionError($e);
                continue;
            }
            $field_group = $this->fields[$field->getFieldType()];
            $field_group->addField($field);
            $this->addInputs($field, $this->request);
        }
        $this->fields_ready = true;
    }

    /**
     * Given a Field object, initializes that field's input(s) to Input objects
     * and adds them to the inputs table
     *
     * @param Field $field
     * @param $request
     */
    private function addInputs(Field $field, $request) {
        $field_type = $field->getFieldType();
        $inputs = $field->getInputs();

        foreach ($inputs as $name => $input_args) {
            try {
                if ($field_type == FieldType::date) {
                    $date_type = (!empty($input_args['date_type'])) ? $input_args['date_type'] : false;
                    $post_types = $this->selectedPostTypes($request);
                    $name = RequestVar::nameToVar($name, $field_type, $date_type);
                    $input = DateInputBuilder::make($name, $input_args, $post_types, $request);
                } else {
                    if ($field_type == FieldType::generic)
                        $name = empty($input_args['id']) ? 'generic' : $input_args['id'];
                    else
                        $name = RequestVar::nameToVar($name, $field_type);
                    $input = InputBuilder::make($name, $field_type, $input_args, $request);
                }
                $this->inputs[] = $input;
            } catch (\InvalidArgumentException $e) {
                $this->addExceptionError($e);
                continue;
            } catch (\Exception $e) {
                $this->addExceptionError($e);
                continue;
            }
        }
    }

    /**
     * Initializes a new Form object and adds all inputs to it
     */
    private function buildForm() {

        try {
            $this->form = new Form($this->args['wpas_id'], $this->args['form']);
        } catch (\Exception $e) {
            $this->addExceptionError($e);
            return;
        }

        foreach ($this->inputs as $input) {
            $this->form->addInput($input);
        }

    }

    /**
     * Initializes and returns a WP_Query object for the search instance
     *
     * @return \WP_Query
     */
    public function buildQueryObject() {
        $query = new Query($this->fields, $this->wp_query_args, $this->request);
        $query = new \WP_Query($query->getArgs());
        $query->query_vars['post_type'] = (empty($this->wp_query_args['post_type'])) ? 'post' : $this->wp_query_args['post_type'];

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $search_query = $this->request->get(RequestVar::search);
        if (!empty($search_query) && $this->relevanssiActive()) {
            relevanssi_do_query($query);
        }

        $this->wp_query_obj = $query;
        return $query;
    }

    /**
     * Adds information abou an exception-based error to the object's error list
     *
     * @param $exception
     */
    private function addExceptionError($exception) {
        $error = array();
        $error['msg'] = $exception->getMessage();
        $error['trace'] = $exception->getTraceAsString();

        $this->errors[] = $error;
    }

    /**
     * Returns an array containing the post types currently being queried
     *
     * @param HttpRequest $request
     * @return array
     */
    private function selectedPostTypes(HttpRequest $request) {
        $wp_query = $this->wp_query_args;
        $val = $request->get(RequestVar::post_type);

        if (!empty($request) && !empty($val)) {
            $post_types = $val;
        } else if (!empty($wp_query) && !empty($wp_query['post_type'])) {
            $post_types = $wp_query['post_type'];
        } else {
            $post_types = array();
        }
        if (!is_array($post_types)) $post_types = array($post_types);
        return $post_types;
    }

    /**
     * Returns the superglobal corresponding to the current form's specified
     * method (eg POST or GET)
     *
     * @param $form_args
     * @return mixed
     */
    private function getRequestGlobal($form_args) {
        if (!empty($form_args['method']) && $form_args['method'] == 'POST') {
            return $_POST;
        }
        return $_GET;
    }

    /**
     * @return bool
     */
    private function relevanssiActive() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return (is_plugin_active('relevanssi/relevanssi.php') || is_plugin_active('relevanssi-premium/relevanssi.php'));
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return (!empty($this->errors));
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return array<Field>
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array<Input>
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @return array
     */
    public function getWPQuery()
    {
        return $this->wp_query_obj;
    }

    /**
     * @return HttpRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

}