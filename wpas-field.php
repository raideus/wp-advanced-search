<?php
/**
 *  Class for configuring and generating a single form field
 *  @since 1.0
 */
class WPAS_Field {
    
    private $id;
    private $name;
    private $classes;
    private $attributes;
    private $label;
    private $type;
    private $format;
    private $placeholder;
    private $values;
    private $nested;
    private $selected = '';
    private $selected_r = array();
    private $exclude = array();
    private $ctr;
    private $pre_html;
    private $post_html;

    public function __construct($field_name, $args = array()) {
        $defaults = array(  'label' => '',
                            'format' => 'select',
                            'placeholder' => false,
                            'values' => array(),
                            'nested' => false,
                            'allow_null' => false,
                            'default_all' => false,
                            'pre_html' => '',
                            'post_html' => ''
                            );

        $this->name = $field_name;
        extract(wp_parse_args($args,$defaults));
        $this->id = (!empty($id)) ? $id : $field_name;

        if (!empty($class)) {
            $this->classes = $class;
            if (is_array($this->classes)) {
                $this->classes = implode(' ', $this->classes);
            }
            $this->classes = ' ' . $this->classes;
        }

        if (!empty($attributes) && is_array($attributes)) {
            $this->attributes = $attributes;
        }

        $this->label = $label;
        $this->type = $type;
        $this->format = $format;
        $this->values = $values;
        $this->nested = $nested;
        $this->placeholder = $placeholder;
        $this->pre_html = $pre_html;
        $this->post_html = $post_html;

        // For select fields, add null value if specified
        if ($format == 'select' && $allow_null && !empty($values)) {

            if ($allow_null === true) {
                $this->add_null_option('');
            } else {
                $this->add_null_option( $allow_null );
            }

        }
        
        if (empty($values) && isset($value)) {
            $this->values = $value;
        }
        
        // Set selected values
        if(isset($_REQUEST[$field_name])) {
            $this->selected = $_REQUEST[$field_name];
            $this->selected_r = $_REQUEST[$field_name];
        } elseif ($default_all && !isset($_REQUEST['wpas']) && ($format == 'checkbox' || $format == 'multi-select')) {
            foreach ($this->values as $value => $label) {
                $this->selected[] = $value;
                $this->selected_r[] = $value;
            }
        } elseif (isset($default) && !isset($_REQUEST['wpas'])) {
            $this->selected = $default;
            $this->selected_r = $default;           
        }

        if (!is_array($this->selected)) {
            $this->selected_r = explode(',',$this->selected);
        }

        // Set excluded values
        if (isset($exclude)) {
            if (is_array($exclude)) {
                $this->exclude = $exclude;
            } else {
                $this->exclude[] = $exclude;
            }
        }

        $this->ctr = 1;

    }

    /**
     * Builds and returns the HTML represetation of the field
     *
     * @since 1.0
     */
    public function build_field() {
        $output = '';

        if ($this->format != 'hidden') {

            $output .= $this->pre_html;

            if (!defined('WPAS_DISABLE_WRAPPERS') || !WPAS_DISABLE_WRAPPERS) {
                $output .= '<div id="wpas-'.$this->id.'" class="wpas-'.$this->id.' wpas-'.$this->type.'-field wpas-field">';
            }

            if ($this->label) {
                $output .= '<div class="label-container"><label for="'.$this->id.'">'.$this->label.'</label></div>';
            }
        }

        switch($this->format) {
            case 'select':
                $output .= $this->select();
                break;
            case 'multi-select':
                $output .= $this->select(true);
                break;
            case 'checkbox':
                $output .= $this->checkbox_field();
                break;
            case 'radio':
                $output .= $this->radio_field();
                break;
            case 'text':
                $output .= $this->text();
                break;
            case 'color':
                $output .= $this->text( $this->format );
                break;
            case 'date':
                $output .= $this->text( $this->format );
                break;
            case 'datetime':
                $output .= $this->text( $this->format );
                break;
            case 'datetime-local':
                $output .= $this->text( $this->format );
                break;
            case 'email':
                $output .= $this->text( $this->format );
                break;
            case 'month':
                $output .= $this->text( $this->format );
                break;
            case 'number':
                $output .= $this->text( $this->format );
                break;
            case 'range':
                $output .= $this->text( $this->format );
                break;
            case 'search':
                $output .= $this->text( $this->format );
                break;
            case 'tel':
                $output .= $this->text( $this->format );
                break;
            case 'time':
                $output .= $this->text( $this->format );
                break;
            case 'url':
                $output .= $this->text( $this->format );
                break;
            case 'week':
                $output .= $this->text( $this->format );
                break;
            case 'textarea':
                $output .= $this->textarea();
                break;
            case 'html':
                $output .= $this->html();
                break;
            case 'hidden':
                $output .= $this->hidden();
                break;
            case 'submit':
                $output .= $this->submit();
                break;
        }

        if ($this->format != 'hidden') {
            if (!defined('WPAS_DISABLE_WRAPPERS') || !WPAS_DISABLE_WRAPPERS) {
                $output .= '</div>';
            }
            $output .= $this->post_html;
        }

        return $output;
    }

    /**
     * Generates a select field
     *
     * @since 1.0
     */
    private function select($multi = false) {

            $output = '<select id="'.$this->id.'" name="'.$this->name;
            if ($multi) {
                $output .= '[]';
            }

            $output .=  '"';
            $output .= ($multi) ? ' multiple="multiple"' : '';
            $output .= '  class="';
            $output .= ($multi) ? 'wpas-multi-select' : 'wpas-select';
            $output .= ' ' . $this->classes.'"';
            $output .= $this->add_attributes();
            $output .= '>';

            if ($this->nested) {
                $output .= $this->build_options_list($this->values, array($this, 'select_option'), 0);
            } else {
                foreach ($this->values as $value => $label) {   
                    if (in_array($value,$this->exclude)) continue;
                    $value = esc_attr($value);
                    $label = esc_attr($label);
                    $output .= $this->select_option($value, $label);
                }
            }

            $output .= '</select>';
            return $output;
    }

    /**
     *  Builds and returns list of field options
     *
     *  Used for select, checkbox, and radio fields.  Supports nested
     *  hierarchies of elements.
     *
     *  @since 1.3
     */
    private function build_options_list($elements = array(), $field_func, $level = 0, $pre = '', $post = '') {
        if (empty($elements)) return "";

        $output = "";
        $output .= $pre;

        foreach($elements as $element) {
            $output .= call_user_func($field_func, $element['value'],$element['label'], $level);
            $output .= $this->build_options_list($element['children'], $field_func, $level+1, $pre, $post);
        }

        $output .= $post;

        return $output;
    }

    /**
     * Generates a single option for a select field
     *
     * @since 1.3
     */
    private function select_option($value, $label, $level = 0) {
        $indent = '';
        if ($level > 0) {
            for($i=0; $i<$level; $i++) {
                $indent .= "—";
            }
            $indent .= ' ';
        }
        $output = '<option value="'.$value.'"';
            if (in_array($value, $this->selected_r)) {
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
    private function checkbox_option($value, $label, $level = 0) {
        $ctr = $this->ctr;
        $el = ($this->nested) ? 'li' : 'div';
        $output = '';
        $output .= '<'.$el.' class="wpas-'.$this->id.'-checkbox-'.$ctr.'-container wpas-'.$this->id.'-checkbox-container wpas-checkbox-container">';
        $output .= '<input type="checkbox" id="wpas-'.$this->id.'-checkbox-'.$ctr.'" class="wpas-'.$this->id.'-checkbox wpas-checkbox'.$this->classes.'" name="'.$this->name.'[]" value="'.$value.'"';
            if (in_array($value, $this->selected_r, true)) {
                $output .= ' checked="checked"';
            }
        $output .= '>';
        $output .= '<label for="wpas-'.$this->id.'-checkbox-'.$ctr.'"> '.$label.'</label></'.$el.'>';   
        $this->ctr++;     
        return $output;
    }


    /**
     * Generates a single option for a radio field
     *
     * @since 1.3
     */
    private function radio_option($value, $label, $level = 0) {
        $ctr = $this->ctr;
        $el = ($this->nested) ? 'li' : 'div';
        $output = '';
        $output .= '<div class="wpas-'.$this->id.'-radio-'.$ctr.'-container wpas-'.$this->id.'-radio-container wpas-radio-container">';
        $output .= '<input type="radio" id="wpas-'.$this->id.'-radio-'.$ctr.'" class="wpas-'.$this->id.'-radio wpas-radio'.$this->classes.'" name="'.$this->name.'" value="'.$value.'"';
            if (in_array($value, $this->selected_r)) {
                $output .= ' checked="checked"';
            }
        $output .= '>';
        $output .= '<label for="wpas-'.$this->id.'-radio-'.$ctr.'"> '.$label.'</label></div>'; 
        $this->ctr++;     
        return $output;
    }

    /**
     * Generates a checkbox field
     *
     * @since 1.0
     */
    private function checkbox_field() {
        $output = '<div class="wpas-'.$this->id.'-checkboxes wpas-checkboxes field-container">';

        if ($this->nested) {
            $output .= $this->build_options_list($this->values, array($this, 'checkbox_option'), 0, '<ul>', '</ul>');
            return $output;
        }

        foreach ($this->values as $value => $label) {
            if (in_array($value,$this->exclude)) continue;
            $value = esc_attr($value);
            $label = esc_attr($label);
            $output .= $this->checkbox_option($value, $label);
        }

        $output .= '</div>';        
        return $output;
    }

    /**
     * Generates a radio field
     *
     * @since 1.0
     */
    private function radio_field() {
        $output = '<div class="wpas-'.$this->id.'-radio-buttons wpas-radio-buttons field-container">';

        if ($this->nested) {
            $output .= $this->build_options_list($this->values, array($this, 'radio_option'), 0, '<ul>', '</ul>');
            return $output;
        }


        foreach ($this->values as $value => $label) {
            if (in_array($value,$this->exclude)) continue;            
            $value = esc_attr($value);
            $label = esc_attr($label);
            $output .= $this->radio_option($value, $label);
        }
        $output .= '</div>';    
        return $output; 
    }

    /**
     * Generates a text input field
     *
     * Also used to generate other HTML5 field types through use of $input_type
     * argument.
     *
     * @since 1.0
     */
    private function text( $input_type = 'text' ) {
        $value = esc_attr($this->get_input_value());
        $placeholder = '';
        if ($this->placeholder)
            $placeholder = ' placeholder="'.$this->placeholder.'"';
        $output = '<input type="'.$input_type.'" id="'.$this->id.'" class="wpas-'.$input_type.''.$this->classes.'" value="'.$value.'" name="'.$this->name.'"'.$placeholder.' '.$this->add_attributes().'>';
        return $output;
    }

    /**
     * Generates a textarea field
     *
     * @since 1.0
     */
    private function textarea() {
        $value = esc_textarea($this->get_input_value());
        $placeholder = '';
        if ($this->placeholder)
            $placeholder = ' placeholder="'.$this->placeholder.'"';
        $output = '<textarea id="'.$this->id.'" class="wpas-textarea'.$this->classes.'" name="'.$this->name.'"'.$placeholder.'  '.$this->add_attributes().'>'.$value.'</textarea>';    
        return $output; 
    }

    /**
     * Generates a submit button
     *
     * @since 1.0
     */
    private function submit() {
        $output = '<input type="submit" class="wpas-submit'.$this->classes.'" value="'.esc_attr($this->values).'" '.$this->add_attributes().'>';
        return $output;
    }

    /**
     * Generates an html field
     *
     * @since 1.0
     */
    private function html() {
        $output = $this->values;
        return $output;
    }

    /**
     * Generates a hidden field
     *
     * @since 1.0
     */
    private function hidden() {
        $value = $this->values;
        if (is_array($value)) {
            $value = reset($value);
        } 
        $value = esc_attr($value);
        $output = '<input type="hidden" name="'.$this->name.'" value="'.$value.'" '.$this->add_attributes().'>';
        return $output;
    }

    /**
     * Returns a string of HTML attributes for inclusion in the field
     *
     * @since 1.2
     */
    private function add_attributes() {
        $output = "";
        if ($this->attributes) {
            foreach($this->attributes as $k => $v) {
                $output .= $k . '="'.$v.'" '; 
            }
        }        
        return $output;
    }

    /**
     * Obtains the value to use in the field.  
     * Used only for text & textarea inputs
     *
     * @since 1.3
     */
    private function get_input_value() {
        $value = '';
        if (is_array($this->selected) && !empty($this->selected[0])) {
            $value = $this->selected[0];
        } elseif (!empty($this->selected)) {
            $value = $this->selected;
        } elseif (is_array($this->values) && !empty($this->values[0])) {
            $value = $this->values[0];
        } else {
            $value = $this->values;
        }   
        return $value;   
    }

    /**
     * For select fields, adds a null option to the beginning of the menu  
     *
     * @since 1.3
     */
    private function add_null_option( $null_label ) {
        $null_option = '';

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

} // class