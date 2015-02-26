<?php
namespace WPAS;
require_once('StdObject.php');
require_once('InputBuilder.php');
require_once('FieldType.php');
require_once('Field.php');
require_once('Factory.php');
require_once('Form.php');
require_once('Validator.php');
require_once('Exceptions.php');

class Factory extends StdObject {

    private $args;
    private $form;
    private $request;
    private $fields;
    private $inputs;
    private $errors;
    private $taxonomy_relation;
    private $meta_key_relation;

    private static $config_defaults = array('taxonomy_relation' => 'AND',
                                            'meta_key_relation' => 'AND',
                                            'form' => array());

    public function __construct($args) {
        $this->args = $this->preProcessArgs($args);
        $this->errors = array();
        $this->fields = $this->initFieldTable();
        $this->inputs = array();
        $this->initFields();
        $this->buildForm();
    }

    private function initFieldTable() {
        $table = array();
        $field_types = FieldType::getConstants();
        foreach($field_types as $type) {
            $table[$type] = array();
        }
        return $table;
    }

    private function preProcessArgs($args) {
        global $post;

        if (!is_array($args)) return array();

        if (empty($args['config'])) {
            $args['config'] = array();
        }

        if(empty($args['config']['form'])) {
            $form_args = (empty($args['form'])) ? array() : $args['form'];
            $args['config']['form'] = $form_args;
        }

        if (empty($args['form']['action']) && is_object($post) && isset($post->ID)) {
            $args['form']['action'] = get_permalink($post->ID);
        }

        $args['config'] = self::parseArgs($args['config'], self::$config_defaults);

        return $args;
    }

    private function initFields() {
        $i = 0;
        if (empty($this->args['fields'])) return;
        foreach ($this->args['fields'] as $f) {
            try {
                $field = new Field($f);
            } catch(Exception $e) {
                $this->addError('Field @ index '. $i . ': ' . $e->getMessage());
                continue;
            }
            $this->fields[$field->getFieldType()][] = $field;
            $this->addInputs($field->getFieldType(), $field->getInputs(),
                $this->request);
            $i++;
        }
    }

    private function addInputs($field_type, array $args, $request) {
        foreach($args as $name => $input_args) {
            try {
                $input = InputBuilder::make($name, $field_type, $input_args, $request);
                $this->inputs[] = $input;
            } catch(\InvalidArgumentException $e) {
                $this->addExceptionError($e);
                continue;
            } catch (ValidationException $e) {
                $this->addExceptionError($e);
                continue;
            }
        }
    }

    private function buildForm() {

        try {
            $this->form = new Form($this->args['config']['form']);
        } catch (ValidationException $e) {
            $this->addExceptionError($e);
            return;
        }

        foreach($this->inputs as $input) {
            $this->form->addInput($input);
        }

    }

    private function addExceptionError($exception) {
        $error = array();
        $error['msg'] = $exception->getMessage();
        $error['trace'] = $exception->getTraceAsString();

        $this->errors[] = $error;
    }

    private function addError($msg) {
        $this->errors[] = $msg;
    }

    /**
     * @return bool
     */
    public function hasErrors() {
        return (!empty($this->errors));
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }




}