<?php
namespace WPAS;
use WPAS\Enum\RequestVar;

class TaxQuery {

    private $request;
    private $query;
    private static $default_format = 'slug';

    function __construct(array $fields, $relation = 'AND', $request) {
        $this->request = $request;
        $this->query = $this->build($fields, $relation, $request);
    }

    /**
     * Build and return a tax_query argument array
     *
     * @param array $fields
     * @param string $relation
     * @param $request
     * @return array
     */
    public function build(array $fields, $relation = 'AND', $request) {
        $query = array();

        if (empty($fields)) return $query;
        foreach ($fields as $field) {
            $group = $this->taxQueryGroup($field, $request);
            if (!empty($group)) $query[] = $group;
        }

        if (count($query) > 1) {
            $query['relation'] = $relation;
        }

        return $query;
    }

    /**
     *
     * Build a tax_query group comprising query arguments and values related
     * to a single taxonomy field
     *
     * @param $field
     * @return array
     */
    public function taxQueryGroup($field) {
        $group = array();
        $taxonomy = $field->getFieldId();
        $inputs = $field->getInputs();

        foreach ($inputs as $input_name => $input) {
            $clause = $this->taxQueryClause($taxonomy, $input_name, $input);
            if (!empty($clause)) $group[] = $clause;
        }

        if (count($group) > 1) {
            $group['relation'] = $field->getRelation();
        }  else if (count($group) == 1) {
            $group = $group[0];
        }
        return $group;
    }

    /**
     * Build a tax_query clause corresponding to a single input
     *
     * @param $taxonomy
     * @param $input_name
     * @param $input
     * @return array
     */
    private function taxQueryClause($taxonomy, $input_name, $input) {
        if (empty($input)) return;
        $request_var = RequestVar::nameToVar($input_name, 'taxonomy');
        $terms = $this->request->get($request_var);

        if (empty($terms)) return;

        $clause = array();
        $clause['taxonomy'] = $taxonomy;
        $clause['operator'] = $input['operator'];
        $clause['field'] = (empty($inputs['term_format'])) ? self::$default_format : $inputs['term_format'];
        $clause['terms'] = $terms;

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