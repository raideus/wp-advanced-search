<?php
namespace WPAS;
require_once('StdObject.php');

class Field extends StdObject {
    private $field_id;
    private $field_type;
    private $inputs;
    private $relation;
    private $group_method;
    private $operator;
    private $compare;

    protected static $rules = array(
        'field_type' => array('type' => 'FieldType', 'required' => true),
        'inputs' => 'array<array>',
        'taxonomy' => 'string',
        'meta_key' => 'string',
        'group' => array('type' => 'string', 'matches' => 'merge|distinct'),
        'relation' => array('type'=>'Relation','required' => true),
        'operator' => array('type'=>'Operator','required' => true),
        'compare' => array('type'=>'Compare','required' => true),
    );

    private static $defaults = array(
        'relation' => 'AND',
        'group' => 'merge',
        'operator' => 'AND',
        'compare' => '=',
    );

    private static $input_args = array(
        'format' => 1,
        'values' => 1,
        'default' => 1,
        'default_all' => 1,
        'field_type' => 1,
        'nested' => 1,
        'placeholder' => 1,
        'class' => 1,
        'attributes' => 1,
        'allow_null' => 1,
        'exclude' => 1,
        'compare' => 1,
        'operator' => 1,
        'pre_html' => 1,
        'post_html' => 1,
        'label' => 1
    );

    public function __construct($args) {
        $args = $this->parseArgs($args,self::$defaults);
        $args = $this->validate($args);
        $this->inputs = array();
        $this->field_type = $args['field_type'];
        $this->relation = $args['relation'];
        $this->group_method = $args['group'];
        $this->operator = $args['operator'];
        $this->compare = $args['compare'];
        $this->populateInputs($args);
    }

    private function populateInputs($args) {
        $field_id = $this->fieldId($args);
        if (!empty($args['inputs'])) {
            $count = count($args['inputs']);
            if ($count == 1) {
                $this->inputs[$field_id] = reset($args['inputs']);
            } else {
                for($i=1; $i<=$count; $i++) {
                    $this->inputs[$field_id.$i] = $args['inputs'][$i-1];
                }
            }
        } else {
            $this->inputs[$field_id] = array_intersect_key($args,
                                                            self::$input_args);
        }
    }

    private function validate($args) {
        $validation = new Validator(self::$rules, $args, self::$defaults);
        if ($validation->fails()) {
            $msg = self::validationErrorMsg($validation->getErrors());
            throw new ValidationException($msg);
        }
        return $validation->getArgs();
    }

    private function fieldId($args) {
        switch($args['field_type']) {
            case 'meta_key' :
                if (empty($args['meta_key'])) {
                    throw new MissingArgumentException('Field is missing '.
                    'argument \'meta_key\'');
                    return;
                }
                return $args['meta_key'];
            case 'taxonomy' :
                if (empty($args['taxonomy'])) {
                    throw new MissingArgumentException('Field is missing '.
                        'argument \'taxonomy\'');
                    return;
                }
                return $args['taxonomy'];
            default:
                return $args['field_type'];
        }
    }

    protected static function parseArgs(array $args, array $defaults) {
        $args = parent::parseArgs($args, self::$defaults);
        $args['field_type'] = $args['type'];
        unset($args['type']);
        return $args;
    }

    /**
     * @return mixed
     */
    public function getFieldType()
    {
        return $this->field_type;
    }

    /**
     * @return mixed
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @return array
     */
    public static function getDefaults()
    {
        return self::$defaults;
    }



}