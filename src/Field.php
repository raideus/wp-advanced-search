<?php
namespace WPAS;
use WPAS\Enum\DataType;

class Field extends StdObject {
    private $field_id;
    private $field_type;
    private $inputs;
    private $relation;
    private $group_method;
    private $operator;
    private $compare;
    private $data_type;

    protected static $rules = array(
        'type' => array('type' => 'FieldType', 'required' => true),
        'inputs' => 'array<array>',
        'taxonomy' => 'string',
        'meta_key' => 'string',
        'data_type' => 'DataType',
        'group_method' => array('type' => 'string', 'matches' => 'merge|distinct'),
        'relation' => array('type'=>'Relation','required' => true),
        'operator' => array('type'=>'Operator','required' => true),
        'compare' => array('type'=>'Compare','required' => true),
    );

    protected static $defaults = array(
        'relation' => 'AND',
        'group_method' => 'distinct',
        'operator' => 'AND',
        'compare' => '=',
        'data_type' => DataType::_default
    );

    private static $input_args = array(
        'format' => 1,
        'values' => 1,
        'value' => 1,
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
        'label' => 1,
        'taxonomy' => 1,
        'terms' => 1,
        'authors' => 1,
        'relation' => 1,
        'data_type' => 1,
        'date_type' => 1,
        'orderby_values' => 1,
        'id' => 1,
        'term_args' => 1
    );

    public function __construct($args) {
        $args = $this->parseArgs($args,self::$defaults);
        $args = self::validate($args);
        $args = $this->postProcessArgs($args);
        $this->inputs = array();
        $this->field_id = $this->setFieldId($args);
        $this->field_type = $args['field_type'];
        $this->relation = $args['relation'];
        $this->group_method = $args['group_method'];
        $this->operator = $args['operator'];
        $this->compare = $args['compare'];
        $this->data_type = $args['data_type'];
        $this->populateInputs($this->field_id, $args);
    }

    private function populateInputs($field_id, $args) {
        if (!empty($args['inputs'])) {
            $count = count($args['inputs']);
            if ($count == 1) {
                $input = $this->addRemainingArgs(reset($args['inputs']), $args);
                $this->inputs[$field_id] = $input;
            } else {
                for($i=1; $i<=$count; $i++) {
                    $input = $this->addRemainingArgs($args['inputs'][$i-1], $args);
                    $this->inputs[$field_id.$i] = $input;
                }
            }
        } else {
            $this->inputs[$field_id] = array_intersect_key($args,
                                                            self::$input_args);
        }
    }

    private function addRemainingArgs($input_args, $field_args) {
        if (isset($field_args['inputs'])) unset($field_args['inputs']);
        $input_args = parent::parseArgs($input_args, $field_args);

        if ($inner_type = DataType::isArrayType($input_args['data_type'])) {
            $input_args['data_type'] = $inner_type;
        }

        return $input_args;
    }

    private function setFieldId($args) {
        switch($args['field_type']) {
            case 'meta_key' :
                if (empty($args['meta_key'])) {
                    throw new \Exception('Field is missing '.
                    'argument \'meta_key\'');
                    return;
                }
                return $args['meta_key'];
            case 'taxonomy' :
                if (empty($args['taxonomy'])) {
                    throw new \Exception('Field is missing '.
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
        return $args;
    }

    private function postProcessArgs(array $args) {
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

    /**
     * @return void
     */
    public function getFieldId()
    {
        return $this->field_id;
    }

    /**
     * @return mixed
     */
    public function getGroupMethod()
    {
        return $this->group_method;
    }

    /**
     * @return mixed
     */
    public function getCompare()
    {
        return $this->compare;
    }

    /**
     * @return mixed
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getDataType()
    {
        return $this->data_type;
    }

}