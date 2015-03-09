<?php
namespace WPAS;

class Validator extends StdObject {

    private $passed;
    private $invalid_args;
    protected $args;
    protected $errors;

    /**
     * Perform validation on an array of arguments against an array of rules
     *
     * If an optional $defaults array is passed, an additional check will be
     * performed to see if any invalid arguments can be replaced with their
     * default value
     *
     * @param array $rules
     * @param array $args
     * @param array $defaults (opitional)
     */
    function __construct( array $rules, array $args, array $defaults = null ) {
        $this->errors = array();
        $this->passed = false;
        $this->args = $args;
        $this->invalid_args = array();
        
        if (empty($rules)) { // If no rules provided, validation must pass
            $this->passed = true;
            return;
        }

        foreach ($rules as $arg => $rule) {
            if ($this->validateRule($arg,$rule) == false) {
                $this->invalid_args[$arg] = $arg;
            }
        }

        if (empty($this->invalid_args)) {
            $this->passed = true;
        }

        if (!$this->passed && $this->canOverride($defaults)) {
            $this->args = $this->defaultOverride($this->args, $defaults,$this->invalid_args);
            $this->passed = true;
        }
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

        if (!empty($this->args[$arg])) {
            return ($this->validateTypes($this->args[$arg], $arg, $types) &&
                    $this->validateMatches($this->args[$arg], $arg, $matches) );
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
     *  @param mixed   value
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
        $this->addTypeError($arg, $types, gettype($value), $value);
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
            throw new \InvalidArgumentException($msg);
            return false;
        }

        return Type::matches($type, $value);
    }

    /**
     * Given an array of default arguments, determine if the invalid
     * arguments can be resolved by swapping in their corresponding default
     *
     * @param array $defaults
     * @return bool
     */
    public function canOverride($defaults) {
        if (empty($this->invalid_args)) return true;
        if (empty($defaults)) return false;
        return (count(array_intersect_key ( $this->invalid_args, $defaults  )) == count($this->invalid_args) );
    }

    /**
     * Given an array of overrides, replaces existing elements in $args
     * with the corresponding element in $defaults and returns the
     * modified $args array
     *
     * Can be used when validation fails to set invalid arguments to
     * their default as a fallback
     *
     * @param array $args
     * @param array $defaults
     * @param array $overrides
     * @return array
     */
    protected function defaultOverride(array $args, array $defaults,
                                              array $overrides) {
        foreach($overrides as $override) {
            if (!empty($defaults[$override])) {
                $args[$override] = $defaults[$override];
            }
        }
        return $args;
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

    /**
     *  Return an array of invalid arguments
     *
     *  @return array
     */
    public function getInvalidArgs() {
        return $this->invalid_args;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }



}
