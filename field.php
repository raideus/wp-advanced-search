<?php

Class WPAS_Field {
	
	public $id;
	public $title;
	public $type;
	public $format;
	public $values;
	public $selected = '';
	public $selected_r = array();

	function __construct($id, $args = array()) {
		$defaults = array(	'title' => '',
							'format' => 'select',
							'values' => array()
							);

		$this->id = $id;
		extract(wp_parse_args($args,$defaults));
		$this->title = $title;
		$this->format = $format;
		$this->values = $values;
		$this->type = $type;

		if (empty($values) && isset($value)) {
			$this->values = $value;
		}
		
		if(isset($_REQUEST[$id])) {
			$this->selected = $_REQUEST[$id];
			$this->selected_r = $_REQUEST[$id];
		}

		if (!is_array($this->selected)) {
	    	$this->selected_r = explode(',',$this->selected);
	    }

	}

	function build_field() {
		echo '<div id="wpas-'.$this->id.'" class="wpas-'.$this->id.' wpas-'.$this->type.'-field  wpas-field">';
		if ($this->title) {
			echo '<label for="'.$this->id.'">'.$this->title.'</label>';
		}
		 	switch($this->format) {
		 		case ('select') :
		 			$this->select();
		 			break;
		 		case ('multi-select') :
		 			$this->select(true);
		 			break;
		 		case ('checkbox') :
		 			$this->checkbox();
		 			break;
		 		case ('radio') :
		 			$this->radio();
		 			break;
		 		case ('text') :
		 			$this->text();
		 			break;
		 		case ('textarea') :
		 			$this->textarea();
		 			break;
		 		case ('html') :
		 			$this->html();
		 			break;
		 		case ('submit') :
		 			$this->submit();
		 			break;
		 	}

		 echo '</div>';
	}

	function select($multi = false) {

	    	if ($multi) {
	    		$multiple = ' multiple="multiple"';
	    	} else {
	    		$multiple = '';
	    	}

			echo '<select id="'.$this->id.'" name="'.$this->id;
			if ($multi) {
				echo '[]';
			}
			echo  '"'.$multiple.'>';

			foreach ($this->values as $value => $label) {	
				$value = esc_attr($value);
				$label = esc_attr($label);
				echo '<option value="'.$value.'"';

					if (in_array($value, $this->selected_r)) {
						echo ' selected="selected"';
					}

				echo '>'.$label.'</option>';
			}

			echo '</select>';
	}

	function checkbox() {
		echo '<div class="wpas-'.$this->id.'-checkboxes wpas-checkboxes">';
		$ctr = 1;
		foreach ($this->values as $value => $label) {
			$value = esc_attr($value);
			$label = esc_attr($label);
			echo '<div class="wpas-'.$this->id.'-checkbox-'.$ctr.'-container wpas-'.$this->id.'-checkbox-container wpas-checkbox-container">';
			echo '<input type="checkbox" id="wpas-'.$this->id.'-checkbox-'.$ctr.'" class="wpas-'.$this->id.'-checkbox wpas-checkbox" name="'.$this->id.'[]" value="'.$value.'"';

				if (in_array($value, $this->selected_r)) {
					echo ' checked="checked"';
				}

			echo '>';

			echo '<label for="wpas-'.$this->id.'-checkbox-'.$ctr.'"> '.$label.'</label></div>';
			$ctr++;
		}
		echo '</div>';		
	}

	function radio() {
		echo '<div class="wpas-'.$this->id.'-radio-buttons">';
		$ctr = 1;
		foreach ($this->values as $value => $label) {
			$value = esc_attr($value);
			$label = esc_attr($label);
			echo '<div class="wpas-'.$this->id.'-radio-'.$ctr.'-container wpas-'.$this->id.'-radio-container wpas-radio-container">';
			echo '<input type="radio" id="wpas-'.$this->id.'-radio-'.$ctr.'" class="wpas-'.$this->id.'-radio wpas-radio" name="'.$this->id.'" value="'.$value.'"';

				if (in_array($value, $this->selected_r)) {
					echo ' checked="checked"';
				}

			echo '>';

			echo '<label for="wpas-'.$this->id.'-radio-'.$ctr.'"> '.$label.'</label></div>';
			$ctr++;
		}
		echo '</div>';		
	}

	function text() {
    	if (is_array($this->selected)) {
    		if (isset($this->selected[0]))
    			$value = $this->selected[0];
    		else
    			$value = '';
    	} elseif (is_array($this->values)) {
    		$value = reset($this->values);
    	} else {
    		$value = $this->values;
    	}
    	$value = esc_attr($value);
    	echo '<input type="text" id="'.$this->id.'" value="'.$value.'" name="'.$this->id.'">';
	}

	function textarea() {
    	if (is_array($this->selected)) {
    		if (isset($this->selected[0]))
    			$value = $this->selected[0];
    		else
    			$value = '';
    	} elseif (is_array($this->values)) {
    		$value = reset($this->values);
    	} else {
    		$value = $this->values;
    	}
    	$value = esc_textarea($value);
    	echo '<textarea  id="'.$this->id.'"name="'.$this->id.'">'.$value.'</textarea>';		
	}

	function submit() {
		echo '<input type="submit" value="'.esc_attr($this->values).'">';
	}

	function html() {
		echo $this->values;
	}

} // Class