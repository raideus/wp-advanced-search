<?php
/**
 *  Generates a form field to display in a search form
 */
Class WPAS_Field {
    
    private $id;
    private $name;
    private $classes;
    private $attributes;
    private $label;
    private $type;
    private $format;
    private $placeholder;
    private $values;
    private $selected = '';
    private $selected_r = array();
    private $exclude = array();

    function __construct($field_name, $args = array()) {
        $defaults = array(  'label' => '',
                            'format' => 'select',
                            'placeholder' => false,
                            'values' => array(),
                            'allow_null' => false,
                            'default_all' => false
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
        $this->placeholder = $placeholder;

        // For select fields, add null value if specified
        if ($format == 'select' && $allow_null && !empty($values)) {
            $arr = array_reverse($this->values, true);
            if ($allow_null === true) {
                $arr[''] = '';
            } else {
                $arr[''] = $allow_null;
            }
            $arr = array_reverse($arr, true);
            $this->values = $arr;
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

    }

    function build_field() {
        if ($this->format != 'hidden') {
            $output = '<div id="wpas-'.$this->id.'" class="wpas-'.$this->id.' wpas-'.$this->type.'-field wpas-field">';
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
                $output .= $this->checkbox();
                break;
            case 'radio':
                $output .= $this->radio();
                break;
            case 'text':
                $output .= $this->text();
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
         $output .= '</div>';
        }
        return $output;
    }

    function select($multi = false) {

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

            foreach ($this->values as $value => $label) {   
                if (in_array($value,$this->exclude)) continue;
                $value = esc_attr($value);
                $label = esc_attr($label);
                $output .= '<option value="'.$value.'"';

                    if (in_array($value, $this->selected_r)) {
                        $output .= ' selected="selected"';
                    }

                $output .= '>'.$label.'</option>';
            }

            $output .= '</select>';
            return $output;
    }

    function checkbox() {
        $output = '<div class="wpas-'.$this->id.'-checkboxes wpas-checkboxes field-container">';
        $ctr = 1;
        foreach ($this->values as $value => $label) {
            if (in_array($value,$this->exclude)) continue;
            $value = esc_attr($value);
            $label = esc_attr($label);
            $output .= '<div class="wpas-'.$this->id.'-checkbox-'.$ctr.'-container wpas-'.$this->id.'-checkbox-container wpas-checkbox-container">';
            $output .= '<input type="checkbox" id="wpas-'.$this->id.'-checkbox-'.$ctr.'" class="wpas-'.$this->id.'-checkbox wpas-checkbox'.$this->classes.'" name="'.$this->name.'[]" value="'.$value.'"';
                if (in_array($value, $this->selected_r, true)) {
                    $output .= ' checked="checked"';
                }
            $output .= '>';
            $output .= '<label for="wpas-'.$this->id.'-checkbox-'.$ctr.'"> '.$label.'</label></div>';
            $ctr++;
        }
        $output .= '</div>';        
        return $output;
    }

    function radio() {
        $output = '<div class="wpas-'.$this->id.'-radio-buttons wpas-radio-buttons field-container">';
        $ctr = 1;
        foreach ($this->values as $value => $label) {
            if (in_array($value,$this->exclude)) continue;            
            $value = esc_attr($value);
            $label = esc_attr($label);
            $output .= '<div class="wpas-'.$this->id.'-radio-'.$ctr.'-container wpas-'.$this->id.'-radio-container wpas-radio-container">';
            $output .= '<input type="radio" id="wpas-'.$this->id.'-radio-'.$ctr.'" class="wpas-'.$this->id.'-radio wpas-radio'.$this->classes.'" name="'.$this->name.'" value="'.$value.'"';
                if (in_array($value, $this->selected_r)) {
                    $output .= ' checked="checked"';
                }
            $output .= '>';
            $output .= '<label for="wpas-'.$this->id.'-radio-'.$ctr.'"> '.$label.'</label></div>';
            $ctr++;
        }
        $output .= '</div>';    
        return $output; 
    }

    function text() {
        if (is_array($this->selected)) {
            if (isset($this->selected[0]))
                $value = $this->selected[0];
            else
                $value = '';
        } elseif (isset($this->selected)) {
            $value = $this->selected;
        } elseif (is_array($this->values)) {
            $value = reset($this->values);
        } else {
            $value = $this->values;
        }
        $value = esc_attr($value);
        $placeholder = '';
        if ($this->placeholder)
            $placeholder = ' placeholder="'.$this->placeholder.'"';
        $output = '<input type="text" id="'.$this->id.'" class="wpas-text'.$this->classes.'" value="'.$value.'" name="'.$this->name.'"'.$placeholder.' '.$this->add_attributes().'>';
        return $output;
    }

    function textarea() {
        if (is_array($this->selected)) {
            if (isset($this->selected[0]))
                $value = $this->selected[0];
            else
                $value = '';
        } elseif (isset($this->selected)) {
            $value = $this->selected;
        } elseif (is_array($this->values)) {
            $value = reset($this->values);
        } else {
            $value = $this->values;
        }
        $value = esc_textarea($value);
        $placeholder = '';
        if ($this->placeholder)
            $placeholder = ' placeholder="'.$this->placeholder.'"';
        $output = '<textarea id="'.$this->id.'" class="wpas-textarea'.$this->classes.'" name="'.$this->name.'"'.$placeholder.'  '.$this->add_attributes().'>'.$value.'</textarea>';    
        return $output; 
    }

    function submit() {
        $output = '<input type="submit" class="wpas-submit'.$this->classes.'" value="'.esc_attr($this->values).'" '.$this->add_attributes().'>';
        return $output;
    }

    function html() {
        $output = $this->values;
        return $output;
    }

    function hidden() {
        $value = $this->values;
        if (is_array($value)) {
            $value = reset($value);
        } 
        $value = esc_attr($value);
        $output = '<input type="hidden" name="'.$this->name.'" value="'.$value.'" '.$this->add_attributes().'>';
        return $output;
    }

    function add_attributes() {
        $output = "";
        if ($this->attributes) {
            foreach($this->attributes as $k => $v) {
                $output .= $k . '="'.$v.'" '; 
            }
        }        
        return $output;
    }

} // Class