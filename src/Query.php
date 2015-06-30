<?php
namespace WPAS;
use WPAS\Enum\FieldType;
use WPAS\Enum\RequestVar;

class Query extends StdObject {

    private $wp_query_args;
    private $fields;
    private $request;
    private $orderby_meta_keys;
    private $query;

    private static $filters = array(
        'search' => 'toScalar',
        'posts_per_page' => 'toScalar',
        'order' => 'toScalar'
    );

    private static function filter($query_var, $value) {
        if (empty(self::$filters[$query_var])) return $value;
        $func = self::$filters[$query_var];
        return call_user_func('self::'.$func,$value);
    }

    private static function toScalar($value) {
        if (is_array($value)) return reset($value);
        return $value;
    }

    public function __construct(array $fields_table, array $wp_query_args, HttpRequest $request) {
        $this->wp_query_args = $wp_query_args;
        $this->fields = $fields_table;
        $this->request = $request;
        $this->orderby_meta_keys = $this->initOrderBy();
        $this->query = $this->build();
    }

    /**
     * Populate orderby_meta_keys table
     *
     * Keeps track of which orderby options (if any) are meta_keys
     * and whether they are character-based or numeric values
     */
    private function initOrderBy() {
        $orderby_meta_keys = array();
        $field_group = $this->fields[FieldType::orderby];
        $orderby_fields = $field_group->getFields();

        if (empty($orderby_fields)) return $orderby_meta_keys;


        $field = $orderby_fields[0];
        $inputs = $field->getInputs();

        if (empty($inputs[FieldType::orderby]) || empty($inputs[FieldType::orderby]['orderby_values'])) {
            return $orderby_meta_keys;
        }

        $values = $inputs[FieldType::orderby]['orderby_values'];

        foreach ($values as $k=>$v) {
            if (isset($v['meta_key']) && $v['meta_key']) {
                if (isset($v['orderby']) && $v['orderby'] == 'meta_value_num') {
                    $type = $v['orderby'];
                } else {
                    $type = 'meta_value';
                }
                $orderby_meta_keys[$k] = $type;
            }
        }

        return $orderby_meta_keys;
    }


    /**
     * Assembles an array of WP_Query arguments
     *
     * @return array
     */
    private function build() {
        $query = array();
        $skip = array('meta_key'=> 1, 'taxonomy'=> 1, 'date'=> 1, 'orderby'=> 1);


        foreach ($this->fields as $type => $field_group) {
            $fields = $field_group->getFields();
            if (empty($fields) || isset($skip[$type])) continue;
            $query = $this->addQueryArg($query, $fields, $this->request);
        }

        $query = $this->addOrderbyArg($query, $this->request);
        $query = $this->addSubQuery($query, $this->fields['taxonomy'], 'taxonomy', $this->request);
        $query = $this->addSubQuery($query, $this->fields['meta_key'], 'meta_key', $this->request);
        $query = $this->addSubQuery($query, $this->fields['date'], 'date', $this->request);
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

        $query[$wp_var] = self::filter($wp_var,$val);
        return $query;
    }

    /**
     * Takes an array of query arguments and adds a sub-query
     * (eg tax_query, meta_query, or date_query)
     *
     * @param array $query
     * @param FieldGroup $field_group
     * @param $field_type
     * @param HttpRequest $request
     * @return array
     */
    private function addSubQuery(array $query, FieldGroup $field_group, $field_type, HttpRequest $request) {
        $classnames = array('taxonomy' => 'TaxQuery', 'meta_key' => 'MetaQuery', 'date' => 'DateQuery');
        $fields = $field_group->getFields();
        if (empty($classnames[$field_type]) || empty($fields)) return $query;

        $s_query = $this->getSubQuery($classnames[$field_type], $fields, $field_group->getRelation(), $request );

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
            $query = new $class($fields[0], $request); // Allow only 1 field for DateQuery
        } else {
            $query = new $class($fields, $relation, $request);
        }
        return $query->getQuery();
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

    public function getArgs() {
        return $this->query;
    }

}