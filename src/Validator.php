<?php
namespace WPAS;
require_once('Type.php');


class Validator {

    private $data;
    private $passed;
    private $errors;

    function __construct( array $rules, array $data ) {
        $this->errors = array();
        $this->passed = false;
        $this->data = $data;
        
        if (empty($rules)) { // If no rules provided, validation must pass
            $this->passed = true;
            return;
        }

        foreach ($rules as $arg => $rule) {
            if ($this->validateRule($arg,$rule) == false) {
                $this->passed = false;
                return;
            }
        }
        $this->passed = true;
    }

    /**
     * Validates an argument against a rule string
     *
     * @param string $arg
     * @param string $rule
     * @return bool
     */
    public function validateRule($arg, $rule) {
        $required = $this->isRequired($rule);
        $types = $this->getTypeString($rule);
        $matches = $this->getMatchesString($rule);

        if (!empty($this->data[$arg])) {
            return ($this->validateTypes($this->data[$arg], $arg, $types) &&
                    $this->validateMatches($this->data[$arg], $arg, $matches) );
        } else if ($required) {
            $this->errors[] = "Argument '".$arg."' required but not provided.";
            return false;
        }
        return true;
    }

    /**
     *  Extract and return the type string from a rule
     *
     *  @param string|array $rule
     *  @return mixed
     */
    public function getTypeString($rule) {
        if (is_array($rule)) {
            return (empty($rule['type'])) ? false : $rule['type'];
        }
        if ($rule == 'required') {
            return false;
        }
        return (is_string($rule)) ? $rule : false;
    }

    /**
     *  Determines if a given argument rule is specified as required.
     *
     *  @param string|array $rule
     *  @return bool
     */
    public function isRequired($rule) {
        if (is_array($rule)) {
            if (!empty($rule['required'])) {
                return $rule['required'];
            }
            return false;
        }
        return ($rule == 'required');
    }

    /**
     *  Returns a 'matches' string from a rule if present.  False
     *  otherwise.
     *
     *  @param string|array $rule
     *  @return mixed
     */
    public function getMatchesString($rule) {
        if (!is_array($rule) || empty($rule['matches'])) {
            return false;
        }
        if (!is_string($rule['matches'])) return false;
        return strtolower($rule['matches']);
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
        if (!$types) return true;// False value indicates no rule was
                                 // provided, so validation passes by default
        $types_r = $this->parseRuleString($types);

        foreach($types_r as $t) {
            if ($this->validateType($value, $t) == true) return true;
        }
        $this->addTypeError($arg, $types, gettype($value));
        return false;
    }

    public function validateMatches($value, $arg, $matches) {
        if (!$matches) return true; // False value indicates no rule was
                                    // provided, so validation passes by default
        $matches_r = $this->parseRuleString($matches);
        $value = (is_string($value)) ? strtolower($value) : $value;
        foreach($matches_r as $m) {
            if ($m == $value) return true;
        }
        return false;
    }

    /**
     *  Validates a value against a type name.
     *
     *  Returns true if $value is of type $type,
     *  false otherwise.
     *
     *  @param mixed   $value
     *  @param string  $type
     *  @return bool
     */
    public static function validateType($value, $type) {
        if (!Type::isValidName($type)) {
            $msg = sprintf("Misformatted type string. '%s' is 
                not a valid type name.", $type);
            throw new \Exception($msg);
            return false;
        }

        return Type::matches($type, $value);
    }

    /**
     *  Parses a string representation of a rule and returns an array
     *  of valid types for that rule.
     *
     *  @param string $str
     *  @return array
     */
    public function parseRuleString($str) {
        if (is_array($str)) return $str;
        return explode('|', $str);
    }

    /**
     *  Add error message to the errors array
     *
     *  @param string $arg
     *  @param string $expected
     *  @param string $got
     *  @return void
     */
    private function addTypeError($arg, $expected, $got) {
        $this->errors[] = sprintf("Invalid argument '%s'.  Expected type '%s'".
                                    " but got '%s'.", $arg, $expected, $got);
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
