<?php
namespace WPAS;
use WPAS\Enum\RequestMethod;

class HttpRequest extends StdObject {

    private $request;

    public static function getVal(array $request, $key, $default = null) {
        if (!isset($request[$key])) return $default;
        $func = is_array($request[$key]) ? 'filter_var_array' : 'filter_var';
        return call_user_func($func, $request[$key], FILTER_SANITIZE_STRING);
    }

    public function __construct(array $request = array()) {
        $this->request = $request;
    }

    public function get($key, $default = null) {
        return self::getVal($this->request, $key, $default);
    }

    public function all() {
        return $this->request;
    }

}

