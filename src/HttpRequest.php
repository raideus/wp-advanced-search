<?php
namespace WPAS;

class HttpRequest {

    private $method;

    static protected $rules = array(
        'method' => 'RequestMethod'
    );

    static protected $defaults = array(
        'method' => RequestMethod::GET
    );

    public function __construct($args) {
        $args = self::validate($args);
    }

}