<?php
namespace WPAS;

class Input extends StdObject {

    private $input_name;
    private $id;
    private $class;
    private $attributes;
    private $label;
    private $field_type;
    private $format;
    private $placeholder;
    private $values;
    private $nested;
    private $selected;
    private $ctr;
    private $pre_html;
    private $post_html;
    private $disable_wrapper;

    protected static $rules = array(
                            'id' => 'string',
                            'attributes' => 'array<scalar>',
                            'field_type' => array('type' => 'FieldType',
                                                  'required' => true),
                            'label' => 'string',
                            'class' => 'array<string>',
                            'format' => array('type' => 'InputFormat',
                                              'required' => true),
                            'placeholder' => 'string|bool',
                            'values' => 'array',
                            'selected' => 'array',
                            'nested' => 'bool',
                            'allow_null' => 'bool|string',
                            'default_all' => 'bool',
                            'pre_html' => 'string',
                            'post_html' => 'string');

    protected static $defaults = array(
                            'label' => '',
                            'placeholder' => false,
                            'values' => array(),
                            'selected' => array(),
                            'nested' => false,
                            'allow_null' => false,
                            'default_all' => false,
                            'disable_wrapper' => false,
                            'pre_html' => '',
                            'post_html' => '' );

    public function __construct($input_name, $args = array()) {
        $args = $this->parseArgs($args,self::$defaults);
        $args = $this->validateInput($input_name, $args, self::$defaults);
        $this->initMembers($input_name, $args);
    }

    /**
     * Validates the input_name and arguments
     *
     * @param string $input_name
     * @param array  $args
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return array
     */
    private function validateInput( $input_name, $args, $defaults ) {
        $args = self::validate($args);

        if (!is_string($input_name)) {
            $err_msg = $this->validationErrorMsg(
                array('Argument 1 `$field_name` ' .
                    'must be a string.'));
            throw new \InvalidArgumentException($err_msg);
        }

        return $args;
    }

    /**
     * Initializes object members
     *
     * @param string $input_name
     * @param array  $args
     */
    private function initMembers($input_name, $args) {
        $this->input_name = $input_name;

        foreach($args as $key => $value) {
            $this->$key = $value;
        }

        // For select fields, add null value if specified
        if ($this->format == 'select' && $this->allow_null && !empty($this->values)) {
            $null_val = ($this->allow_null === true) ? '' : $this->allow_null;
            $this->addNullOption($null_val);
        }

        if (!empty($this->class) && is_array($this->class)) {
            $this->class = implode(' ', $this->class);
        }

        $this->id = $this->input_name;
        $this->ctr = 1;
    }

    /**
     * Returns a string containing the full HTML content of the input, including
     * a wrapper div
     *
     * @return string
     */
    public function toHTML()
    {
        $markup = new InputMarkup($this);
        return $markup->generate();
    }

    /**
     * For select fields, adds a null option to the beginning of the menu
     *
     * @since 1.3
     */
    private function addNullOption( $null_label ) {
        if ($this->nested) {
            $null_option = array(
                'value' => '',
                'label' => $null_label,
                'children' => array()
            );
        } else {
            $null_option = $null_label;
        }

        $arr = array_reverse($this->values, true);
        $arr[''] = $null_option;
        $arr = array_reverse($arr, true);
        $this->values = $arr;
    }

    public function disableWrapper() {
        $this->disable_wrapper = true;
    }

    public function wrappersDisabled() {
        return $this->disable_wrapper;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getInputName()
    {
        return $this->input_name;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getFieldType() {
        return $this->field_type;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function isNested()
    {
        return $this->nested;
    }

    public function getSelected()
    {
        return $this->selected;
    }

    public function getPreHtml()
    {
        return $this->pre_html;
    }

    public function getPostHtml()
    {
        return $this->post_html;
    }

}