<?php
namespace WPAS;
use WPAS\Enum\RequestVar;
use WPAS\Enum\Compare;
use WPAS\Enum\DataType;

class MetaQuery {

    private $request;
    private $query;

    function __construct(array $fields, $relation = 'AND', $request) {
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
    public function build(array $fields, $relation = 'AND', $request) {
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
    public function metaQueryGroup($field, $request) {
        $group = array();
        $meta_key = $field->getFieldId();
        $inputs = $field->getInputs();
        $data_type = $field->getDataType();
        $compare = $field->getCompare();

        $clauses = array();

        if ($compare == Compare::between) {
            $clauses[] = $this->metaQueryClauseBetween($meta_key, $inputs, 2);
        } else {
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
        }

        foreach ($clauses as $clause) {
            if (!empty($clause) && !empty($clause['value'])) {
               $group[] = $clause;
            }
        }

        if (count($group) == 1) $group = $group[0];
        return $group;
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
        if (empty($this->request[$request_var])) return array();

        $clause = array();
        $clause['key'] = $meta_key;
        $clause['compare'] = $input['compare'];
        $clause['value'] = $this->request[$request_var];
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

        foreach($inputs as $v => $input) {
            $v =  RequestVar::nameToVar($v, 'meta_key');
            if (!isset($this->request[$v])) {
                continue;
            }

            $var = $this->request[$v];

            // Disallow multi-value inputs
            // Reason: Doing a BETWEEN comparison between two multi-value
            // groups is undefined behavior
            $var = (is_array($var) ) ? array(reset($var)) : array($var);

            $clause['value'] = array_merge($clause['value'], $var);

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
        if (empty($this->request[$request_var])) return array();

        $clause = array();
        $data_type = DataType::isArrayType($input['data_type']);

        $var = $this->request[$request_var];
        if (!is_array($var)) $var = array($var);

        foreach($var as $value) {
            $subclause = array();
            $subclause['key'] = $meta_key;
            $subclause['type'] = $data_type;
            $subclause['value'] = $value;
            $subclause['compare'] = $input['compare'];

            $clause[] = $subclause;
        }

        if (count($clause) > 1) {
            $clause['relation'] = $input['relation'];
        }

        if (count($clause) == 1) $clause = $clause[0];

        return $clause;
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