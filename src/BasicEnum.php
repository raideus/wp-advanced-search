<?php
namespace WPAS;
require_once('StdObject.php');

abstract class BasicEnum extends StdObject {
    private function __construct() {}

    public static function isValidName($name, $strict = false) {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value) {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict = true);
    }

    public static function isValid($name, $strict = false) {
        return (self::isValidName($name, $strict) || self::isValidValue($name));
    }
}
