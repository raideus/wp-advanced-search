<?php
namespace WPAS\Enum;

class DataType extends BasicEnum {
    const NUMERIC = 'NUMERIC';
    const BINARY = 'BINARY';
    const CHAR = 'CHAR';
    const DATE = 'DATE';
    const DATETIME = 'DATETIME';
    const DECIMAL = 'DECIMAL';
    const SIGNED = 'SIGNED';
    const TIME = 'TIME';
    const UNSIGNED = 'UNSIGNED';
    const _ARRAY = 'ARRAY';
    const _default = self::CHAR;

    public static function isArrayType($data_type) {
        $pattern = '/^'.self::_ARRAY.'<?([a-zA-Z]*)>/';
        $matches = array();
        if ( !preg_match($pattern, $data_type, $matches) ) {
            return false;
        }
        return ( (count($matches) == 2) ? $matches[1] : false );
    }

    public static function isValidValue($value) {
        if ($inner_type = self::isArrayType($value)) {
            return parent::isValidValue($inner_type);
        }
        return parent::isValidValue($value);
    }
}