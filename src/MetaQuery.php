<?php
namespace WPAS;
require_once('Compare.php');

class MetaQuery {

    private $request;
    private $query;

    function __construct(array $fields, $relation = 'AND', $request) {
        $this->query = $this->build($fields, $relation, $request);
    }

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


    public function metaQueryGroup($field, $request) {
        $group = array();
        $meta_key = $field->getFieldId();
        $inputs = $field->getInputs();
        $data_type = $field->getDataType();
        $compare = $field->getCompare();

        if ($compare == Compare::between) {
            $group[] = $this->metaQueryClauseBetween($meta_key, $inputs, 2);
            return $group;
        }

        // Disallow multiple input sources if not using BETWEEN comparison
        // This is a (potentially) temporary restriction
        $inputs = array(array_keys($inputs)[0] => $inputs[array_keys($inputs)[0]]);
        //
        //

        foreach ($inputs as $input_name => $input) {
            if (DataType::isArrayType($data_type)) {
                $clause = $this->metaQueryClauseArray($meta_key, $input_name, $input);
            } else {
                $clause = $this->metaQueryClause($meta_key, $input_name, $input);
            }
            if (!empty($clause)) $group[] = $clause;
        }

        if (count($group) == 1) $group = $group[0];
        return $group;
    }

    private function metaQueryClause($meta_key, $input_name, $input) {
        if (empty($input)) return;

        $request_var = RequestVar::nameToVar($input_name, 'meta_key');
        if (empty($this->request[$request_var])) return;

        $clause = array();
        $clause['key'] = $meta_key;
        $clause['compare'] = $input['compare'];
        $clause['value'] = $this->request[$request_var];
        $clause['type'] = $input['data_type'];

        return $clause;
    }

    private function metaQueryClauseBetween($meta_key, array $inputs, $limit = false) {
        if (empty($inputs)) return;
        $clause = array();

        $clause['key'] = $meta_key;
        $clause['type'] = reset($inputs)['data_type'];
        $clause['value'] = array();
        $clause['compare'] = Compare::between;

        $count = 1;

        foreach($inputs as $v => $input) {
            $v =  RequestVar::nameToVar($v, 'meta_key');
            if (empty($this->request[$v])) continue;

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
            $clause['compare'] = reset($inputs)['compare'];
        }

        return $clause;
    }

    private function metaQueryClauseArray($meta_key, $input_name, $input) {
        $request_var = RequestVar::nameToVar($input_name, 'meta_key');
        if (empty($this->request[$request_var])) return;

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
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

}