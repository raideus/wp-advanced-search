<?php
namespace WPAS;

class InputMarkup extends StdObject {

    private $input;
    private $ctr;

    // Input types which use the standard 'input' HTML tag
    private static $input_types = array(
        'text' => 1,
        'color' => 1,
        'date' => 1,
        'datetime' => 1,
        'datetime-local' => 1,
        'email' => 1,
        'month' => 1,
        'number' => 1,
        'range' => 1,
        'search' => 1,
        'tel' => 1,
        'time' => 1,
        'url' => 1,
        'week' => 1
    );

    public function __construct(Input $input) {
        $this->input = $input;
    }

    public function generate() {
        $output = '';
        $format = str_replace('-','_',$this->input->getFormat());

        if ($format != 'hidden') {
            $output .= $this->input->getPreHtml();
            $output .= $this->openWrapper();
            $output .= $this->inputLabel();
        }

        $output .= $this->generateInnerMarkup($format);

        if ($format != 'hidden') {
            $output .= $this->closeWrapper();
            $output .= $this->input->getPostHtml();
        }

        return $output;
    }

    private function generateInnerMarkup($format) {
        if (isset(self::$input_types[$format])) {
            return $this->input($format);
        }
        return $this->$format();
    }

    private function openWrapper() {
        $output = '';
        $id = $this->input->getId();
        if ($this->input->wrappersDisabled() === false) {
            $output .= '<div id="wpas-'.$id.'"  class="wpas-'
                .$id.' wpas-'.$this->input->getFieldType().'-field wpas-field">';
        }
        return $output;
    }

    private function closeWrapper() {
        $output = '';
        if ($this->input->wrappersDisabled() === false) {
            $output .= '</div>';
        }
        return $output;
    }

    private function inputLabel() {
        $output = '';
        $label = $this->input->getLabel();
        if ($label) {
            $output .= '<div class="label-container">' .
                '<label for="' . $this->input->getId() . '">' . $label . '</label></div>';
        }
        return $output;
    }

    /**
     * Generates a select field
     */
    private function select($multi = false) {

        $output = '<select id="'.$this->input->getId().'" name="'.$this->input->getInputName();
        if ($multi) {
            $output .= '[]';
        }

        $output .=  '"';
        $output .= ($multi) ? ' multiple="multiple"' : '';
        $output .= '  class="';
        $output .= ($multi) ? 'wpas-multi-select' : 'wpas-select';
        $output .= ' ' . $this->input->getClass().'"';
        $output .= $this->attributesString();
        $output .= '>';

        if ($this->input->isNested()) {
            $output .= $this->buildOptionsList($this->input->getValues(),
                array($this, 'selectOption'), 0);
        } else {
            foreach ($this->input->getValues() as $value => $label) {
                $output .= $this->selectOption($value, $label);
            }
        }

        $output .= '</select>';
        return $output;
    }

    /**
     * Generates a checkbox field
     *
     */
    private function checkbox() {
        return $this->listField(true);
    }

    /**
     * Generates a radio field
     *
     */
    private function radio() {
        return $this->listField(false);
    }

    /**
     * Generates a list-style field (either checkboxes or radio buttons)
     *
     * @param bool $is_checkbox
     * @return string
     */
    private function listField($is_checkbox = true) {
        $group_label = ($is_checkbox) ? 'checkboxes' : 'radio-buttons';
        $option_func = ($is_checkbox) ? 'checkboxOption' : 'radioOption';

        $output = '<div class="wpas-'.$this->input->getId().'-'.$group_label.' wpas-'.$group_label.' field-container '.$this->input->getClass().'">';

        if ($this->input->isNested()) {
            $output .= $this->buildOptionsList($this->input->getValues(), array($this, $option_func), 0, true);
        } else {
            foreach ($this->input->getValues() as $value => $label) {
                $output .= call_user_func(array($this,$option_func), $value, $label);
            }
        }

        $output .= '</div>';
        return $output;
    }

    /**
     *  Builds and returns list of field options
     *
     *  Used for select, checkbox, and radio fields.  Supports nested
     *  hierarchies of elements.
     *
     * @param array $elements
     * @param $field_func
     * @param int $level
     * @param bool $list_el
     * @return string
     */
    private function buildOptionsList($elements = array(), $field_func, $level = 0,
                                      $list_el = false) {
        if (empty($elements)) return "";

        $output = "";
        $output .= ($list_el) ? '<ul>' : '';

        foreach($elements as $element) {
            $output .= call_user_func($field_func, $element['value'], $element['label'], $level);
            $output .= $this->buildOptionsList($element['children'], $field_func, $level+1, $list_el);
        }

        $output .= ($list_el) ? '</ul>' : '';

        return $output;
    }

    /**
     * Generates a text input field
     *
     * Also used to generate other HTML5 field types through use of $input_type
     * argument.
     *
     */
    private function input( $input_type = 'text' ) {
        $value = $this->getInputValue();
        $placeholder = '';
        if ($this->input->getPlaceholder())
            $placeholder = ' placeholder="'.$this->input->getPlaceholder().'"';
        $output = '<input type="'.$input_type.'" id="'.$this->input->getId().'" class="wpas-'.$input_type.' '.$this->input->getClass().'" value="'.$value.'" name="'.$this->input->getInputName().'"'.$placeholder.' '.$this->attributesString().'>';
        return $output;
    }

    /**
     * Generates a textarea field
     *
     */
    private function textarea() {
        $value = $this->getInputValue();
        $placeholder = '';
        if ($this->input->getPlaceholder())
            $placeholder = ' placeholder="'.$this->input->getPlaceholder().'"';
        $output = '<textarea id="'.$this->input->getId().'" class="wpas-textarea '.$this->input->getClass().'" name="'.$this->input->getInputName().'"'.$placeholder.'  '.$this->attributesString().'>'.$value.'</textarea>';
        return $output;
    }

    /**
     * Generates a submit button
     *
     *
     */
    private function submit() {
        return $this->buttonInput('submit');
    }

    /**
     * Generates a reset button
     *
     * @since 1.4
     */
    private function reset() {
        return $this->buttonInput('reset');
    }

    private function buttonInput($type) {
        $values = $this->input->getValues();
        $value = reset($values);
        $output = '<input type="'.$type.'" class="wpas-'.$type.' '.$this->input->getClass().'" value="'.$value.'" '.$this->attributesString().'>';
        return $output;
    }

    /**
     * Generates a clear button
     *
     * @since 1.4
     */
    private function clear() {
        $values = $this->input->getValues();
        $value = reset($values);
        $output = '<button class="wpas-clear '.$this->input->getClass().'" '.$this->attributesString().'>'.$value.'</button>';
        return $output;
    }

    /**
     * Generates an html field
     */
    private function html() {
        $values = $this->input->getValues();
        return reset($values);
    }

    /**
     * Generates a hidden field
     */
    private function hidden() {
        $values = $this->input->getValues();
        $value = reset($values);
        $output = '<input type="hidden" name="'.$this->input->getInputName().'" value="'.$value.'" '.$this->attributesString().'>';
        return $output;
    }

    /**
     * Creates a string of HTML element attributes for the input
     */
    private function attributesString() {
        $output = "";
        if ($this->input->getAttributes()) {
            foreach($this->input->getAttributes() as $k => $v) {
                $output .= $k . '="'.$v.'" ';
            }
        }
        return $output;
    }

    /**
     * Obtains the value to use in the field.
     *
     * Used only for text & textarea inputs
     *
     * @since 1.3
     */
    private function getInputValue() {
        $value = '';
        $selected = $this->input->getSelected();
        $values = $this->input->getValues();

        if (!empty($selected)) {
            $value = reset($selected);
        } else if (!empty($values)) {
            $value = reset($values);
        }
        return $value;
    }

    /**
     * Generates a single option for a select field
     *
     * @since 1.3
     */
    private function selectOption($value, $label, $level = 0) {
        $indent = '';
        if ($level > 0) {
            for($i=0; $i<$level; $i++) {
                $indent .= "—";
            }
            $indent .= ' ';
        }
        $output = '<option value="'.$value.'"';
        if (in_array($value, $this->input->getSelected())) {
            $output .= ' selected="selected"';
        }
        $output .= '>'.$indent.$label.'</option>';
        return $output;
    }

    /**
     * Generates a single option for a checkbox field
     *
     * @since 1.3
     */
    private function checkboxOption($value, $label, $level = 0) {
        return $this->listOption($value, $label, 'checkbox');
    }

    /**
     * Generates a single option for a radio field
     *
     * @since 1.3
     */
    private function radioOption($value, $label, $level = 0) {
        return $this->listOption($value, $label, 'radio');
    }

    /**
     * Generates a single field option for a checkbox or radio field
     *
     * @param $value
     * @param $label
     * @param string $type "checkbox" or "radio"
     * @return string
     */
    private function listOption($value, $label, $type = 'checkbox') {
        $ctr = $this->ctr;
        $element = ($this->input->isNested()) ? 'li' : 'div';
        $id = $this->input->getId();
        $name = $this->input->getInputName();
        $name .= ($type == 'checkbox') ? '[]' : '';
        $output = '<'.$element.' class="wpas-'.$id.'-'.$type.'-'.$ctr.'-container wpas-'.$id.'-'.$type.'-container wpas-'.$type.'-container">'
                   . '<input type="'.$type.'" id="wpas-'.$id.'-'.$type.'-'.$ctr.'" class="wpas-'.$id.'-'.$type.' wpas-'.$type.'" name="'.$name.'" value="'.$value.'"';
        if (in_array($value, $this->input->getSelected(), true)) {
            $output .= ' checked="checked"';
        }
        $output .= '>';
        $output .= '<label for="wpas-'.$id.'-'.$type.'-'.$ctr.'"> '.$label.'</label></'.$element.'>';
        $this->ctr++;
        return $output;
    }

    private function multi_select() {
        return $this->select(true);
    }

    private function text() {
        return $this->input('text');
    }

    private function number() {
        return $this->input('number');
    }

    private function color() {
        return $this->input('color');
    }

    private function url() {
        return $this->input('url');
    }

    private function email() {
        return $this->input('email');
    }

    private function tel() {
        return $this->input('tel');
    }

    private function date() {
        return $this->input('date');
    }

    private function datetime() {
        return $this->input('datetime');
    }

    private function datetime_local() {
        return $this->input('datetime-local');
    }

    private function time() {
        return $this->input('time');
    }

    private function week() {
        return $this->input('week');
    }

}
