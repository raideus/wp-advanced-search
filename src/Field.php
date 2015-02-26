<?php
namespace WPAS;

class Field {
    private $id;
    private $inputs;
    private $relation;

    protected static $rules = array(
        'id' => array('type' => 'RequestVar', 'required' => true),
        'inputs' => 'array',
        'relation' => array('type' => 'string', 'matches' => 'AND|OR'),
    );

    private static $defaults = array(
        'relation' => 'AND',
        'inputs' => array()
    );

    private static $input_vars = array(
        'format' => 1,
        'values' => 1,
        'default' => 1,
        'nested' => 1,
        'placeholder' => 1,
        'class' => 1,
        'attributes' => 1,
        'allow_null' => 1,
        'exclude' => 1,
        'compare' => 1,
    );

    public function __construct($request_var, $args) {
        $args = $this->parseArgs($args,self::$defaults);
        $this->validate($args);

        $this->id = $request_var;
        $this->inputs = $args['inputs'];
        $this->relation = $args['relation'];

        if (empty($this->inputs)) $this->populateInput($args);
    }

    private function populateInput($args) {
        $this->inputs = array();
        $input_args = array();
        foreach($args as $arg => $val) {
            if (!empty(self::$input_vars[$arg])) {
                $input_args[$arg] = $val;
            }
        }
        $this->inputs[0] = $this->parseInputArgs($input_args);
    }



}