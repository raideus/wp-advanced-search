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
    private $config;
    private $orderby_meta_keys;
    private $fields_ready;

    private static $config_defaults = array(
        'taxonomy_relation' => 'AND',
        'meta_key_relation' => 'AND',
        'form' => array());

    public function __construct($args, $request = null) {
        $this->args = $this->preProcessArgs($args);
        $this->config = $this->args['config'];
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

        if (empty($args['config'])) {
            $args['config'] = array();
        }

        if (empty($args['wpas_id'])) {
            $args['wpas_id'] = 'default';
        }

        if (empty($args['config']['form'])) {
            $form_args = (empty($args['form'])) ? array() : $args['form'];
            $args['config']['form'] = $form_args;
        }

        if (empty($args['form']['action']) && is_object($post) && isset($post->ID)) {
            $args['config']['form']['action'] = get_permalink($post->ID);
        }

        if (!isset($args['wp_query'])) $args['wp_query'] = array();

        $args['config'] = self::parseArgs($args['config'], self::$config_defaults);

        return $args;
    }

    /**
     * Process and sanitize an array of request variables
     *
     * @param $request
     * @return mixed
     */
    private function processRequest($request) {
        $request = (empty($request)) ? $_REQUEST : $request;
        if (empty($request)) return $request;
        foreach ($request as $k => $v) {
            $request[$k] = $this->sanitizeRequestVar($v);
        }
        return $request;
    }

    /**
     * Sanitize a request variable
     *
     * @param $var
     * @return string|void
     */
    private function sanitizeRequestVar($var) {
        if (is_scalar($var)) return esc_attr($var);
        foreach ($var as $i => $el) {
            $var[$i] = esc_attr($el);
        }
        return $var;
    }

    /**
     * Populate fields table
     */
    private function initFields() {
        $i = 0;
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
            $i++;
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
        if ($this->fields_ready == false) return;
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
                    $input = InputBuilder::makeDate($name, $input_args, $post_types, $request);
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
            $this->form = new Form($this->args['wpas_id'], $this->args['config']['form']);
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
        $query->query_vars['post_type'] = $query_args['post_type'];


        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if (!empty($_REQUEST['search_query']) && $this->relevanssiActive()) {
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
    public function buildQuery() {
        $query = array();
        if (!$this->fields_ready) {
            $this->addError('Method buildQuery called before initializing' .
                'query fields.  Must call initFields first.');
            return $query;
        }

        $meta_query = array();
        $tax_query = array();

        foreach ($this->fields as $type => $fields) {
            if (empty($fields)) continue;
            switch ($type) {
                case 'meta_key' :
                    $meta_query = new MetaQuery($fields,$this->config['meta_key_relation'], $this->request);
                    $meta_query = $meta_query->getQuery();
                    break;
                case 'taxonomy' :
                    $tax_query = new TaxQuery($fields, $this->config['taxonomy_relation'], $this->request);
                    $tax_query = $tax_query->getQuery();
                    break;
                case 'date' :
                    $field = reset($fields); // Only one field permitted for date query
                    $date_query = new DateQuery($field, $this->request);
                    $date_query = $date_query->getQuery();
                    break;
                case 'orderby' :
                    $query = $this->addOrderbyArg($query, $this->request);
                    break;
                default :
                    $query = $this->addQueryArg($query, $fields, $this->request);
            }
        }

        if (!empty($meta_query)) $query['meta_query'] = $meta_query;
        if (!empty($tax_query))  $query['tax_query'] = $tax_query;
        if (!empty($date_query)) $query['date_query'] = $date_query;

        $query = $this->addPaginationArg($query);

        return self::parseArgs($query, $this->wp_query_args);
    }

    /**
     * Takes an array of query arguments and adds an orderby argument
     *
     * @param array $query
     * @param array $request
     * @return array
     */
    private function addOrderbyArg(array $query, array $request) {
        $var = RequestVar::orderby;

        if (empty($request[$var])) return $query;
        $orderby_val = $request[$var];
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
     * @param array $request
     * @param bool $wp_var
     * @return array
     */
    private function addQueryArg(array $query, array $fields, array $request,
                                 $wp_var = false) {
        if (empty($fields)) return $query;
        $field = reset($fields); // As of v1.4, only one field allowed per
                                 // query var (other than taxonomy and meta_key)
        $field_id = $field->getFieldId();

        $var = RequestVar::nameToVar($field_id);

        $wp_var = RequestVar::wpQueryVar($field_id);
        $wp_var = (!$wp_var) ? $var : $wp_var;

        if (empty($request[$var])) return $query;

        $query[$wp_var] = $request[$var];
        return $query;
    }

    private function addPaginationArg(array $query) {
        if ( get_query_var('paged') ) {
            $paged = get_query_var('paged');
        } else if ( get_query_var('page') ) {
            $paged = get_query_var('page');
        } else {
            $paged = 1;
        }
        $query['paged' ] = $paged;
        return $query;
    }


    private function addExceptionError($exception) {
        $error = array();
        $error['msg'] = $exception->getMessage();
        $error['trace'] = $exception->getTraceAsString();

        $this->errors[] = $error;
    }

    private function addError($msg) {
        $this->errors[] = $msg;
    }

    /**
     * Returns an array containing the post types currently being queried
     *
     * @param array $request
     * @return array
     */
    private function selectedPostTypes(array $request) {
        $wp_query = $this->wp_query_args;
        if (!empty($request) && !empty($request[RequestVar::post_type])) {
            $post_types = $request[RequestVar::post_type];
        } else if (!empty($wp_query) && !empty($wp_query['post_type'])) {
            $post_types = $wp_query['post_type'];
        } else {
            $post_types = array();
        }
        if (!is_array($post_types)) $post_types = array($post_types);
        return $post_types;
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