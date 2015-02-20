<?php 
namespace WPAS;
require_once('StdObject.php');
require_once('Validator.php');

class Form extends StdObject {
    private $id;
    private $action;
    private $method;
    private $name;
    private $class;
    private $fields;
    private $field_objects;
    protected $args;
    static protected $rules = array(
                                    'action' => 'string',
                                    'method' => 'FormMethod',
                                    'id' => 'string',
                                    'name' => 'string',
                                    'class' => 'string|array<string>',
                                    'fields' => 'array' );
    static protected $defaults = array(  
                                        'action' => '',
                                        'method' => 'GET',
                                        'id' => 'wp-advanced-search',
                                        'name' => 'wp-advanced-search',
                                        'class' => 'wp-advanced-search',
                                        'fields' => array() );

    function __construct( $args ) {
        $this->args = $this->parseArgs($args,self::$defaults);
        $this->validate();

        foreach($this->args as $key => $value) {
            $this->$key = $value;
        }
    }

    public function toHTML() {
        $output = "
        <form id=\"".$this->id."\" name=\"".$this->name."\" 
                class=\"".implode(" ",$this->class)."\"  
                method=\"".$this->method."\"  
                action=\"".$this->action."\"> ";


        // TODO


        $output .= "</form>";
    }

    public function getID() {
        return $this->id;
    }

    public function getAction() {
        return $this->action;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getName() {
        return $this->name;
    }

    public function getClass() {
        return $this->class;
    }

    public function validate() {
        $validation = new Validator(self::$rules, $this->args);
        if ($validation->passes()) return;

        $errors = $validation->getErrors();
        $err_msg = $this->validationErrorMsg($errors);
        throw new \Exception($err_msg);
        die;
    }

}
