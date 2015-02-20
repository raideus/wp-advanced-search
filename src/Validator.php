<?php
namespace WPAS;
require_once('Type.php');

class Validator {

    private $passed;
    private $errors;

    function __construct( array $rules, array $data ) {
        $this->errors = array();
        $this->passed = false;
        
        if (empty($rules)) { // If no rules provided, validation must pass
            $this->passed = true;
            return;
        } 

        foreach($data as $key => $value) {
            if (empty($rules[$key])) continue;
            if ($this->validateTypes($value, $key, $rules[$key]) == false) {
                $this->passed = false;
                return;
            }
        }
        $this->passed = true;
    }

    /**
     *  Parses a string representation of a rule and returns an array
     *  of valid types for that rule.
     *
     *  @param string $str
     *  @return array
     */
    public function parseRule($str) {
        if (is_array($str)) return $str;
        return explode('|', $str);
    }

    /**
     *  Validates a value against a formatted string of allowed types.
     *
     *  Returns true if $value matches at least one allowed type,
     *  false otherwise.
     *
     *  @param mixed   $data
     *  @param string  $arg
     *  @param string  $types
     *  @return bool
     */
    public function validateTypes($value, $arg, $types) {
        $types_r = $this->parseRule($types);

        foreach($types_r as $t) {
            if ($this->validateType($value, $t) == true) return true;
        }
        $this->addError($arg, $types, gettype($value));
        return false;
    }

    /**
     *  Validates a value against a type name.
     *
     *  Returns true if $value is of type $type,
     *  false otherwise.
     *
     *  @param mixed   $data
     *  @param string  $type
     *  @return bool
     */
    public function validateType($value, $type) {
        if (!Type::isValidName($type)) {
            $msg = sprintf("Misformatted type string. '%s' is 
                not a valid type name.", $type);
            throw new \Exception($msg);
            return false;
        }

        return Type::matches($type, $value);
    }

    /**
     *  Add error message to the errors array
     *
     *  @param string $arg
     *  @param string $expected
     *  @param string $got
     *  @return void
     */
    private function addError($arg, $expected, $got) {
        $this->errors[] = sprintf("Invalid argument '%s'.  Expected type '%s' 
                                    but got '%s'.", $arg, $expected, $got);
    }

    /**
     *  Verify whether a variable contains an array of strings
     *
     *  @param string $arr
     *  @return bool
     */
    private function isArrayOfStrings($arr) {
        if (!is_array($arr)) return false;

        foreach ($arr as $a) {
            if (!is_string($a)) {
                return false;
            }
        }     

        return true;   
    }

    /**
     *  Verify whether a variable contains an array of scalars
     *
     *  @param string $arr
     *  @return bool
     */
    private function isArrayOfScalars($arr) {
        if (!is_array($arr)) return false;

        foreach ($arr as $a) {
            if (!is_scalar($a)) {
                return false;
            }
        }     

        return true;   
    }

    /**
     *  Boolean function indicating whether the validation test passed.
     *  Returns true if validation passed, false otherwise.
     *
     *  @return bool
     */
    public function passes() {
        return ($this->passed == true);
    }

    /**
     *  Boolean function indicating whether the validation test failed.
     *  Returns true if validation failed, false otherwise.
     *
     *  @return bool
     */
    public function fails() {
        return ($this->passed == false);
    }

    /**
     *  Return an array of errors
     *
     *  @return array
     */
    public function getErrors() {
        return $this->errors;
    }

}
