<?php
namespace WPAS;

class Field extends StdObject {
    protected $type;
    protected $inputs;
    protected $query_args;
    protected $term_format;
    protected $relation;

    function __construct($type, $args) {
        $this->validate($type);
    }

    public function validate($type) {
        if (FieldType::isValid($type)) return;
        throw new ValidationException('Argument 1 `$type` ' .
                'must be a valid FieldType.');
    }

    public function addInputVar($var) {
        if (is_string($var)) $this->input_vars[] = $var;
    }
}