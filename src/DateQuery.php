<?php
namespace WPAS;
use WPAS\Enum\RequestVar;

class DateQuery {

    private $request;
    private $query;

    function __construct($field, HttpRequest $request) {
        $this->query = $this->build($field, $request);
    }

    public function build($field, $request) {
        $query = array();
        $this->request = $request;

        if (empty($field)) return $query;
        $inputs = $field->getInputs();

        if (empty($inputs)) return $query;

        // Temporary restriction (v1.4), allow only one input for date
        // TODO: Expand functionality to support multiple inputs
        $input = reset($inputs);


        switch($input['date_type']) {
            case 'year':
                $query = $this->addYear($query, $this->request);
                break;
            case 'month' :
                $query = $this->addMonth($query, $this->request);
                break;
            case 'day' :
                $query = $this->addDay($query, $this->request);
                break;
        }

        return $query;
    }

    private function addYear($query, $request) {
        $var = RequestVar::date_year;
        $value = $this->getRequestVar($var, $request);
        if ($value == false) return $query;

        $query['year'] = $value;

        return $query;
    }

    private function addMonth($query, $request) {
        $var = RequestVar::date_month;
        $value = $this->getRequestVar($var, $request);
        if ($value == false) return $query;

        $value = explode('-',$value);
        if(count($value) != 2) return $query;

        $query['year'] = $value[0];
        $query['month'] = $value[1];

        return $query;
    }

    private function addDay($query, $request) {
        $var = RequestVar::date_day;
        $value = $this->getRequestVar($var, $request);
        if ($value == false) return $query;

        $value = explode('-', $value);
        if(count($value) != 3) return $query;

        $query['year'] = $value[0];
        $query['month'] = $value[1];
        $query['day'] = $value[2];

        return $query;
    }

    private function getRequestVar($var, $request) {
        $val = $request->get($var, null);
        if ($val === null) return false;
        if (is_array($val)) implode(",",$val);
        return $val;
    }


    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

}