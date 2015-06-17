<?php
namespace WPAS;
use WPAS\Enum\RequestMethod;

class HttpRequest extends StdObject {

    private $request;

    static protected $rules = array(
        'method' => 'RequestMethod'
    );

    static protected $defaults = array(
        'method' => RequestMethod::GET
    );

    public function __construct(array $request = array()) {
        $this->request = $request;
    }

    public function get($key, $default = null) {
        if (!isset($this->request[$key])) return $default;
        $func = is_array($this->request[$key]) ? 'filter_var_array' : 'filter_var';
        return call_user_func($func, $this->request[$key], FILTER_SANITIZE_STRING);
    }

}

