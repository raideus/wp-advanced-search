<?php
namespace WPAS;
use WPAS\Enum\FieldType;
use WPAS\Enum\RequestVar;

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

    private static $arg_defaults = array(
        'form' => array(),
        'fields' => array(),
        'taxonomy_relation' => 'AND',
        'meta_key_relation' => 'AND',
        'debug' => false,
        'debug_level' => 'log'
    );

    public function __construct($args, $request = null) {
        $this->args = $this->preProcessArgs($args);
        $this->wp_query_args = $this->args['wp_query'];
        $this->errors = array();
        $this->inputs = array();
        $this->fields_ready = false;
        $this->request = $this->processRequest($request);
        $this->fields = $this->initFieldTable();
        $this->initFields();
        $this->initOrderby();
        $this->buildForm();
    }

    /**
     * Creates empty table of fields as $field_type => array() pairs
     *
     * @return array
     */
    private function initFieldTable() {
        $table = array();
        $field_types = FieldType::getConstants();
        foreach ($field_types as $type) {
            $table[$type] = array();
        }
        return $table;
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

        if (empty($args['wpas_id'])) {
            $args['wpas_id'] = 'default';
        }

        if (empty($args['form'])) {
            $args['form'] = (empty($args['config']['form'])) ? array() : $args['form'];
        }

        if (empty($args['form']['action']) && is_object($post) && isset($post->ID)) {
            $args['form']['action'] = get_permalink($post->ID);
        }

        if (!isset($args['wp_query'])) $args['wp_query'] = array();

        $args = self::parseArgs($args, self::$arg_defaults);

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
            $this->fields[$field->getFieldType()][] = $field;
            $this->addInputs($field, $this->request);
        }
        $this->fields_ready = true;
    }

    /**
     * Populate orderby_meta_keys table
     *
     * Keeps track of which orderby options (if any) are meta_keys
     * and whether they are character-based or numeric values
     */
    private function initOrderBy() {
        if ($this->fields_ready === false) return;
        if (empty($this->fields[FieldType::orderby])) return;

        $this->orderby_meta_keys = array();

        $field = $this->fields[FieldType::orderby][0];
        $inputs = $field->getInputs();
        if (empty($inputs[FieldType::orderby]) || empty($inputs[FieldType::orderby]['orderby_values'])) {
            return;
        }

        $values = $inputs[FieldType::orderby]['orderby_values'];

        foreach ($values as $k=>$v) {
            if (isset($v['meta_key']) && $v['meta_key']) {
                if (isset($v['orderby']) && $v['orderby'] == 'meta_value_num') {
                    $type = $v['orderby'];
                } else {
                    $type = 'meta_value';
                }
                $this->orderby_meta_keys[$k] = $type;
            }
        }
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
                    $date_type = (empty($input_args['date_type'])) ? $input_args['date_type'] : false;
                    $post_types = $this->selectedPostTypes($request);
                    $name = RequestVar::nameToVar($name, $field_type, $date_type);
                    $input = DateInputBuilder::make($name, $input_args, $post_types, $request);
                } else {
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
        $query_args = $this->buildQuery();
        $query = new \WP_Query($query_args);
        $query->query_vars['post_type'] = (empty($query_args['post_type'])) ? 'post' : $query_args['post_type'];


        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $search_query = $this->request->get(RequestVar::search);
        if (!empty($search_query) && $this->relevanssiActive()) {
            relevanssi_do_query($query);
        }

        $this->wp_query_obj = $query;
        return $query;
    }

    /**
     * Assembles an array of WP_Query arguments
     *
     * @return array
     */
    private function buildQuery() {
        $query = array();
        $skip = array('meta_key'=> 1, 'taxonomy'=> 1, 'date'=> 1, 'orderby'=> 1);

        if (!$this->fields_ready) {
            $this->addError('Method buildQuery called before initializing' .
                ' query fields.  Must call initFields first.');
            return $query;
        }

        foreach ($this->fields as $type => $fields) {
            if (empty($fields) || isset($skip[$type])) continue;
            $query = $this->addQueryArg($query, $fields, $this->request);
        }

        $query = $this->addOrderbyArg($query, $this->request);
        $query = $this->addSubQuery($query, $this->fields, 'taxonomy', $this->request);
        $query = $this->addSubQuery($query, $this->fields, 'meta_key', $this->request);
        $query = $this->addSubQuery($query, $this->fields, 'date', $this->request);
        $query = $this->addPaginationArg($query);

        return self::parseArgs($query, $this->wp_query_args);
    }

    /**
     * Takes an array of query arguments and adds an orderby argument
     *
     * @param array $query
     * @param HttpRequest $request
     * @return array
     */
    private function addOrderbyArg(array $query, HttpRequest $request) {
        $var = RequestVar::orderby;
        $val = $request->get($var);

        if (empty($val)) return $query;
        $orderby_val = $val;
        $orderby_val = (is_array($orderby_val)) ? implode(" ",$orderby_val) : $orderby_val;

        if (array_key_exists($orderby_val, $this->orderby_meta_keys)) {
            $query[$var] = $this->orderby_meta_keys[$orderby_val];
            $query['meta_key'] = $orderby_val;
        } else {
            $query[$var] = $orderby_val;
        }

        return $query;
    }

    /**
     * Adds and argument to an array of query arguments
     *
     * @param array $query
     * @param array $fields
     * @param HttpRequest $request
     * @param bool $wp_var
     * @return array
     */
    private function addQueryArg(array $query, array $fields, HttpRequest $request) {
        if (empty($fields)) return $query;
        $field = reset($fields); // As of v1.4, only one field allowed per
                                 // query var (other than taxonomy and meta_key)
        $field_id = $field->getFieldId();

        $var = RequestVar::nameToVar($field_id);

        $wp_var = RequestVar::wpQueryVar($field_id);
        $wp_var = (!$wp_var) ? $var : $wp_var;

        $val = $request->get($var);

        if (empty($val)) return $query;

        $query[$wp_var] = $val;
        return $query;
    }

    /**
     * Takes an array of query arguments and adds a sub-query
     * (eg tax_query, meta_query, or date_query)
     *
     * @param array $query
     * @param array $fields_table
     * @param $field_type
     * @param HttpRequest $request
     * @return array
     */
    private function addSubQuery(array $query, array $fields_table, $field_type, HttpRequest $request) {
        $classnames = array('taxonomy' => 'TaxQuery', 'meta_key' => 'MetaQuery', 'date' => 'DateQuery');
        if (empty($classnames[$field_type]) || empty($fields_table[$field_type])) return $query;

        $fields = $fields_table[$field_type];
        $s_query = $this->getSubQuery($classnames[$field_type], $fields, $field_type.'_relation', $request );

        if (!empty($s_query)) $query[RequestVar::wpQueryVar($field_type)] = $s_query;
        return $query;
    }

    /**
     * Creates and returns a sub-query
     * (eg tax_query, meta_query, or date_query)
     *
     * @param $class
     * @param $fields
     * @param $relation
     * @param HttpRequest $request
     * @return mixed
     */
    private function getSubQuery($class, $fields, $relation, HttpRequest $request) {
        $class = 'WPAS\\'.$class;
        if ($class == 'WPAS\DateQuery') {
            return (new $class($fields[0], $request))->getQuery(); // Allow only 1 field for DateQuery
        } else {
            return (new $class($fields, $this->args[$relation], $request))->getQuery();
        }
    }

    /**
     * Adds pagination argument to an array of query arguments
     *
     * @param array $query
     * @return array
     */
    private function addPaginationArg(array $query) {
        $page_num = $this->request->get('paged');
        if (!empty($page_num)) {
            $paged = $page_num;
        }
        else if ( get_query_var('paged') ) {
            $paged = get_query_var('paged');
        } else if ( get_query_var('page') ) {
            $paged = get_query_var('page');
        } else {
            $paged = 1;
        }
        $query['paged' ] = $paged;
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
     * Adds a standard error message to the object's error list
     * @param $msg
     */
    private function addError($msg) {
        $this->errors[] = $msg;
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
    public function hasErrors() {
        return (!empty($this->errors));
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return array
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
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @return array
     */
    public function getWPQueryArgs()
    {
        return $this->wp_query_args;
    }

    /**
     * @return array
     */
    public function getWPQuery()
    {
        return $this->wp_query_obj;
    }

    /**
     * @return bool
     */
    public function relevanssiActive() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return (is_plugin_active('relevanssi/relevanssi.php') || is_plugin_active('relevanssi-premium/relevanssi.php'));
    }

}