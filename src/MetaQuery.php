<?php
namespace WPAS;
use WPAS\Enum\RequestVar;
use WPAS\Enum\Compare;
use WPAS\Enum\DataType;

class MetaQuery {

    private $request;
    private $query;

    function __construct(array $fields, $relation = 'AND', HttpRequest $request) {
        $this->query = $this->build($fields, $relation, $request);
    }

    /**
     * Build and return a meta_query argument array
     *
     * @param array $fields
     * @param string $relation
     * @param $request
     * @return array
     */
    public function build(array $fields, $relation = 'AND', HttpRequest $request) {
        $query = array();
        $this->request = $request;

        if (empty($fields)) return $query;
        foreach ($fields as $field) {
            $group = $this->metaQueryGroup($field, $request);
            if (!empty($group)) $query[] = $this->metaQueryGroup($field, $request);
        }

        if (count($query) > 1) $query['relation'] = $relation;

        return $query;
    }

    /**
     * Build a meta_query group comprising query arguments and values related
     * to a single meta_key
     *
     * @param $field
     * @param $request
     * @return array
     */
    private function metaQueryGroup($field, HttpRequest $request) {
        $group = array();
        $meta_key = $field->getFieldId();
        $inputs = $field->getInputs();
        $data_type = $field->getDataType();
        $compare = $field->getCompare();

        $clauses = $this->clauseList($inputs, $meta_key, $compare, $data_type);

        foreach ($clauses as $clause) {
            if (!empty($clause)) {
               $group[] = $clause;
            }
        }
        if (count($group) == 1) $group = $group[0];
        return $group;
    }

    /**
     * Construct array of meta_query clauses
     *
     * @param $inputs
     * @param $meta_key
     * @param $compare
     * @param $data_type
     * @return array
     */
    private function clauseList($inputs, $meta_key, $compare, $data_type) {
        if ($compare == Compare::between) {
            return array($this->metaQueryClauseBetween($meta_key, $inputs, 2));
        }

        $clauses = array();

        // Disallow multiple input sources if not using BETWEEN comparison
        // This is a (potentially) temporary restriction
        $keys = array_keys($inputs);
        $inputs = array($keys[0] => $inputs[$keys[0]]);
        //
        //

        foreach ($inputs as $input_name => $input) {
            if (DataType::isArrayType($data_type)) {
                $clause = $this->metaQueryClauseArray($meta_key, $input_name, $input);
            } else {
                $clause = $this->metaQueryClause($meta_key, $input_name, $input);
            }
            $clauses[] = $clause;
        }

        return $clauses;

    }

    /**
     * Build a meta_query clause corresponding to a single input
     *
     * @param $meta_key
     * @param $input_name
     * @param $input
     * @return array
     */
    private function metaQueryClause($meta_key, $input_name, $input) {
        if (empty($input)) return array();

        $request_var = RequestVar::nameToVar($input_name, 'meta_key');
        $val = $this->request->get($request_var);
        if (empty($val)) return array();

        $clause = array();
        $clause['key'] = $meta_key;
        $clause['compare'] = $input['compare'];
        $clause['value'] = $val;
        $clause['type'] = $input['data_type'];

        return $clause;
    }

    /**
     * Build a meta_query clause for a BETWEEN relationship
     *
     * @param $meta_key
     * @param array $inputs
     * @param bool $limit
     * @return array
     */
    private function metaQueryClauseBetween($meta_key, array $inputs, $limit = false) {
        if (empty($inputs)) return array();
        $first_input = reset($inputs);
        $clause = array();

        $clause['key'] = $meta_key;
        $clause['type'] = $first_input['data_type'];
        $clause['value'] = array();
        $clause['compare'] = Compare::between;
        $count = 1;

        foreach($inputs as $name => $input) {
            $clause['value'] = $this->mergeRequestValues($clause['value'], $name);
            $count++;
            if ($limit && $count > $limit) break;
        }

        if (!empty($compare)) {
            $clause['compare'] = $compare;
        } else if (count($inputs) == 1) {
            $clause['compare'] = $first_input['compare'];

            // Support single-input BETWEEN fields using range values, i.e. ['0-10','11-25']
            if (count($clause['value']) == 1) {
                $clause = $this->adaptClauseForSingleInput($clause);
            }
        }

        if ( empty($clause['value']) ) return '';
        if ( empty($clause['value'][0]) && !is_numeric($clause['value'][0])) return '';

        return $clause;
    }

    /**
     * Appends a value from the HTTP request an array of values
     * Used for constructing multi-input BETWEEN comparisons
     *
     * @param array $values  Existing values array
     * @param $name          Request var name
     * @return array
     */
    private function mergeRequestValues(array $values, $name) {
        $name =  RequestVar::nameToVar($name, 'meta_key');
        $val = $this->request->get($name, null);

        if ($val === null) {
            return $values;
        }

        // Disallow multi-value inputs
        // Reason: Doing a BETWEEN comparison between two multi-value
        // groups is undefined behavior
        $val = (is_array($val) ) ? array(reset($val)) : array($val);

        return array_merge($values, $val);
    }

    /**
     *  Build a meta_query clause for meta values which are stored as an array
     *
     *  Creates a separate sub-clause for each value being queried
     *
     * @param $meta_key
     * @param $input_name
     * @param $input
     * @return array
     */
    private function metaQueryClauseArray($meta_key, $input_name, $input) {
        $request_var = RequestVar::nameToVar($input_name, 'meta_key');
        $var = $this->request->get($request_var);
        if (empty($var)) return array();

        $clause = array();

        if (!is_array($var)) $var = array($var);

        foreach($var as $value) {
            $clause[] = $this->subClause($meta_key, $input, $value);
        }

        if (count($clause) > 1) {
            $clause['relation'] = $input['relation'];
        }

        if (count($clause) == 1) $clause = $clause[0];

        return $clause;
    }

    private function subClause($meta_key, $input, $value) {
        return array(
            'key' => $meta_key,
            'type' => DataType::isArrayType($input['data_type']),
            'value' => $value,
            'compare' => $input['compare']
        );
    }

    /**
     * Adapts meta_query clause for single-input fields using range values
     * of the form ['0:10','11:24','25:']
     *
     * @param $clause
     * @return array
     */
    private function adaptClauseForSingleInput($clause) {
        if (empty($clause['value'])) return $clause;
        if (substr($clause['value'][0],-1) == ':') {
            $clause['value'] = substr($clause['value'][0],0,-1);
            $clause['compare'] = Compare::greq;
            return $clause;
        }
        if (substr($clause['value'][0],0,1) == ':') {
            $clause['value'] = substr($clause['value'][0],1);
            $clause['compare'] = Compare::leq;
            return $clause;
        }
        $clause['value'] = explode(":", $clause['value'][0]);
        return $clause;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

}