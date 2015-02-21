<?php
/**
 *  Class for configuring and generating a single form field
 *  @since 1.0
 */
namespace WPAS;
require_once('StdObject.php');
require_once('Validator.php');
class Input extends StdObject {
    
    private $id;
    private $name;
    private $class;
    private $attributes;
    private $label;
    private $type;
    private $format;
    private $placeholder;
    private $values;
    private $nested;
    private $selected;
    private $exclude;
    private $ctr;
    private $pre_html;
    private $post_html;

    protected $args;

    protected static $rules = array(
                            'id' => 'string',
                            'attributes' => 'array<scalar>',
                            'field_type' => array('type' => 'FieldType', 'required' => true),
                            'label' => 'string',
                            'format' => array('type' => 'InputFormat', 'required' => true),
                            'placeholder' => 'string|bool',
                            'values' => 'array<scalar>',
                            'selected' => 'array<string>',
                            'exclude' => 'array<string>',
                            'nested' => 'bool',
                            'allow_null' => 'bool|string',
                            'default_all' => 'bool',
                            'pre_html' => 'string',
                            'post_html' => 'string');

    private static $defaults = array(
                            'label' => '',
                            'format' => 'select',
                            'placeholder' => false,
                            'values' => array(),
                            'nested' => false,
                            'allow_null' => false,
                            'default_all' => false,
                            'pre_html' => '',
                            'post_html' => '' );

    public function __construct($field_name, $args = array()) {
        $this->args = $this->parseArgs($args,self::$defaults);
        $this->validate($field_name, $args);
        foreach($args as $key => $value) {
            $this->$key = $value;
        }
    }

    public function validate( $field_name, $args ) {
        $validation = new Validator(self::$rules, $args);
        if ($validation->fails()) {
            $errors = $validation->getErrors();
            $err_msg = $this->validationErrorMsg($errors);
            throw new \Exception($err_msg);
            die;
        }
        
        if (!is_string($field_name)) {
            $err_msg = $this->validationErrorMsg(
                                array('Argument 1 `$field_name` ' .
                                        'must be a string.'));
            throw new \Exception($err_msg);               
        }
    }

//    public function getID() {
//        return $this->id;
//    }
//
//    public function getName() {
//        return $this->name;
//    }
//
//    public function getClass() {
//        return $this->class;
//    }
//
//    public function getAttributes() {
//        return $this->attributes;
//    }
//
//    public function getLabel() {
//        return $this->label;
//    }

}