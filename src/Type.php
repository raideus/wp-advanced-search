<?php
namespace WPAS;

/**
 *  Type class
 *
 *  Used for defining and validating data types
 */
class Type extends StdObject {

    private function __construct() {}

    // Array of allowed data types in $k => $v format
    // If $v is an array, $v corresponds to valid types which are
    // a subset of that type
    private static $types = array(
        "string" => true,
        "numeric" => true,
        "bool" => true,
        "scalar" => array("string", "numeric", "bool"),
        "array" => true,
        "object" => array("Input", "Form"),
        "RequestMethod" => true,
        "InputFormat" => true,
        "FieldType" => true,
        "RequestVar" => true,
        "Operator" => true,
        "Relation" => true,
        "Compare" => true,
        "DataType" => true
    );

    // Validation functions for each type
    private static $validate = array(
        "string" => "is_string",
        "numeric" => "is_numeric",
        "bool" => "self::isBool",
        "scalar" => "is_scalar",
        "array" => "is_array",
        "object" => "is_object",
        "RequestMethod" => array("\WPAS\Enum\RequestMethod", "isValid"),
        "InputFormat" => array("\WPAS\Enum\InputFormat", "isValid"),
        "FieldType" => array("\WPAS\Enum\FieldType", "isValid"),
        "RequestVar" => array("\WPAS\Enum\RequestVar", "isValidValue"),
        'Operator' => array("\WPAS\Enum\Operator", "isValidValue"),
        'Compare' => array("\WPAS\Enum\Compare", "isValidValue"),
        'Relation' => array("\WPAS\Enum\Relation", "isValidValue"),
        'DataType' => array("\WPAS\Enum\DataType", "isValidValue")
    );

    /**
     *  Check if a string representing a type name is a valid type
     *
     *  @param string $name
     *  @return bool
     */
    public static function isValidName($name) {
        if (!is_string($name)) return false;
        if (!empty($name) && isset(self::$types[$name])) {
            return true;
        }

        if ($subtype = self::isTypedArray($name)) {
            return self::isValidName($subtype);
        }

        return false;
    }

    /**
     *  Check if a type name corresponds to an array type
     *
     *  @param string $type
     *  @return bool
     */    
    private static function isArray($type) {
        return (substr($type, 0, 5) == 'array');
    }

    /**
     *  Check if a type name corresponds to a typed array
     *
     *  @param string $type
     *  @return bool
     */   
    private static function isTypedArray($type) {
        $pattern = '/^array<?([a-zA-Z]*)>/';
        $matches = array();
        if ( !preg_match($pattern, $type, $matches) ) {
            return false;
        }
        return ( (count($matches) == 2) ? $matches[1] : false );
    }

    /**
     *  Return the basic type name of a given type string.
     *
     *  eg: 
     *  getBasicType("array<string>") returns "array"
     *  getBasicType("bool") returns "bool"
     *
     *  @param string $type
     *  @return mixed
     */ 
    private static function getBasicType($type) {
        if (!self::isValidName($type)) return false;
        return (self::isArray($type)) ? 'array' : $type;
    }

    /**
     *  Check if a type name is considered a subtype of another type
     *
     *  @param string $type
     *  @param string $superset_type
     *  @return bool
     */   
    public static function isSubtypeOf($type, $superset_type) {

        if (!self::isValidName($type) || !self::isValidName($superset_type)) {
            return false;
        }

        if ($type == $superset_type) return true;
        if (isset(self::$types[$superset_type]) &&
                                     is_array(self::$types[$superset_type])) {
            $subtypes = self::$types[$superset_type];
            if (in_array($type, $subtypes, true) === TRUE) {
                return true;
            }
        }

        if ( ($subtype = self::isTypedArray($type)) == false ) {
            return false;
        }

        if (!self::isArray($superset_type)) return false;


        if ( ($super_subtype = self::isTypedArray($superset_type)) == false ) {
            return true;
        }

        return self::isSubtypeOf($subtype, $super_subtype);
    }

    /**
     *  Checks if a given value is of a specified type
     *
     *  @param  string $type
     *  @param  string $value
     *  @return bool
     */
    public static function matches($type, $value) {
        if (!self::isValidName($type)) return false;
        $basic = self::getBasicType($type);

        if (!call_user_func(self::$validate[$basic], $value)) {
            return false;
        }

        if ($subtype = self::isTypedArray($type)) {
            foreach($value as $v) {
                if (!call_user_func(self::$validate[$subtype], $v)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Flexible type checker for boolean values.  Will accept strings
     * equal to 'true','false','0', or '1' in addition to real booleans
     *
     * @param $val
     * @return bool
     */
    public static function isBool($val) {
        switch ($val) {
            case is_bool($val) :
                return true;
            case !is_string($val) :
                return false;
            case strtolower($val) == 'true' :
            case strtolower($val) == 'false' :
            case strtolower($val) == '1' :
            case strtolower($val) == '0' :
                return true;
                break;
        }
        return false;
    }


}