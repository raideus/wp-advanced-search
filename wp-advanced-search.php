<?php
/**
 * WP Advanced Search - v1.0
 *
 * A PHP framework for building advanced search forms in WordPress
 * 
 * Built by Growth Spark
 * http://growthspark.com
 *
 * Contributors: Sean Butze
 *
 */

define('WPAS_DEBUG', true);

if (!class_exists('WP_Advanced_Search')) {
	class WP_Advanced_Search {

		// Query Data
		public $wp_query;
		public $wp_query_args = array();
		public $taxonomy_operators = array();
		public $meta_keys = array();

		// Form Data
		public $selected_taxonomies = array();
		public $selected_meta_keys = array();
		public $selected_authors = array();
		public $selected_post_types = array();
		public $selected_years = array();
		public $selected_months = array();
		public $selected_days = array();

		function __construct($args = '') {
			if ( !empty($args) ) {
				$this->process_args($args);
			}
			$this->process_form_input();

			add_action('wp_enqueue_scripts', array($this, 'styles') );
		}


		function styles() {
			wp_enqueue_style( 'wp-advanced-search',  get_template_directory_uri() . '/wp-advanced-search/css/wp-advanced-search.css', array(), '1', 'all' );
		}


		/**
		 * (new) Parses arguments and sets variables
		 *
		 * @since 1.0
		 */
		function process_args( $args ) {

			if (isset($args['wp_query'])) {
				$this->wp_query_args = $args['wp_query'];
			}

			if (isset($args['fields'])) {
				$this->fields = $args['fields'];
			}

			$fields = $this->fields;
			foreach ($fields as $field) {

				if (isset($field['type'])) {
					switch($field['type']) {

						case ('taxonomy') :
							if (isset($field['taxonomy'])) {
								$tax = $field['taxonomy'];

								if (isset($field['operator'])) {
									$operator = $field['operator'];
								} else {
									$operator = 'AND';
								}

								$this->taxonomy_operators[$tax] = $operator;
							}
							break;

						case('meta_key') :
							if (isset($field['meta_key'])) {
								$meta = $field['meta_key'];

								if (isset($field['compare'])) {
									$operator = $field['compare'];
								} else {
									$operator = '=';
								}

								if (isset($field['data_type'])) {
									$data_type = $field['data_type'];
								} else {
									$data_type = 'CHAR';
								}

								$this->meta_keys[$meta]['compare'] = $operator;
								$this->meta_keys[$meta]['data_type'] = $data_type;
							}
							break;		

					}
				}

			}

		}

	    /**
		 * Generates and displays the search form
		 *
		 * @since 1.0
		 */
	    function the_form() {
	    	global $post;
	    	global $wp_query;

	    	$url = get_permalink($post->ID);
	    	$fields = $this->fields;
	    	$has_search = false;
	    	$has_submit = false;

	    	if (isset($_REQUEST['filter_page'])) {
	    		$page = $_REQUEST['filter_page'];
	    	} else {
	    		$page = 1;
	    	}

			// Display the filter form
	    	echo '<form name="uber-posts-filter" class="uber-posts-filter" method="GET" action="'.$url.'">';

	    		// URL fix if pretty permalinks are not enabled
		    	if ( get_option('permalink_structure') == '' ) { 
		    		echo '<input type="hidden" name="page_id" value="'.$post->ID.'">'; 
		    	}

		    	foreach ($fields as $field) {
					if (isset($field['type'])) {
						if ($field['type'] == 'taxonomy') {
							$this->tax_field($field);
						} elseif ($field['type'] == 'meta_key') {
							$this->meta_field($field);
						} elseif ($field['type'] == 'author') {
							$this->author_field($field);
						} elseif ($field['type'] == 'date') {
							$this->date_field($field);
						} elseif ($field['type'] == 'post_type') {
							$this->post_type_field($field);
						} elseif ($field['type'] == 'search' && !$has_search) {
							$this->search_field($field);
							$has_search = true;
						} elseif ($field['type'] == 'submit' && !$has_submit) {
							$this->submit_button($field);
							$has_submit = true;
						}
					}
		    	}

	    	echo '</form>';

	    }


	    /**
		 * Generates a search field
		 *
		 * @since 1.0
		 */
	    function search_field( $args ) {

	    	$defaults = array(
	    					'title' => 'Search',
	    					'std' => 'Enter search terms...'
	    				);

	    	$args = wp_parse_args($args, $defaults);

	    	if (isset($_REQUEST['search_query'])) {
	    		$value = $_REQUEST['search_query'];
	    	} else {
	    		$value = $args['std'];
	    	}

	    	?>
	    	<div id="wpas-search" class="wpas-field">
	    		<label for="wpas-search-field"><?php echo esc_attr($args['title']); ?> </label>
	    		<input id="wpas-search-field" type="text" name="search_query" value="<?php echo esc_attr($value); ?>">
	    	</div>
	    	<?php

	    }

	    /**
		 * Generates a submit button
		 *
		 * @since 1.0
		 */
	    function submit_button( $args ) {
	    	$defaults = array('value' => 'Search');
	    	$args = wp_parse_args($args, $defaults);
	    	?>
	    	<div id="wpas-submit" class="wpas-field">
	    		<input type="submit" value="<?php echo esc_attr($args['value']); ?>">
	    	</div>
	    	<?php
	    }

	    /**
		 * Generates a text field
		 *
		 * @since 1.0
		 */
	    function text_field( $id, $title, $value ) {
	    	$id = esc_attr($id);
	    	$title = esc_attr($title);
	    	if (is_array($value)) {
	    		if (isset($value[0]))
	    			$value = $value[0];
	    		else
	    			$value = '';
	    	}
	    	$value = esc_attr($value);
	    	?>
	    	<div id="wpas-<?php echo $id; ?>" class="wpas-field wpas-text-field">
	    		<label for="text-<?php echo $id; ?>"><?php echo $title; ?></label>
	    		<input type="text" value="<?php echo $value; ?>" name="<?php echo $id; ?>">
	    	</div>
	    	<?php
	   	}    

	    /**
		 * Generates a textarea field
		 *
		 * @since 1.0
		 */
	    function textarea_field( $id, $title, $value ) {
	    	$id = esc_attr($id);
	    	$title = esc_attr($title);
	    	if (is_array($value)) {
	    		if (isset($value[0]))
	    			$value = $value[0];
	    		else
	    			$value = '';
	    	}
	    	$value = esc_textarea($value);
	    	?>
	    	<div id="wpas-<?php echo $id; ?>" class="wpas-field wpas-textarea-field">
	    		<label for="text-<?php echo $id; ?>"><?php echo $title; ?></label>
	    		<textarea type="text" name="<?php echo $id; ?>">
	    			<?php echo trim($value); ?>
	    		</textarea>
	    	</div>
	    	<?php
	   	}    


	    /**
		 * Generates a form field containing terms for a given taxonomy
	     *
		 * @param array $args Arguments for configuring the field.
		 * @since 1.0
		 */
	    function tax_field( $args ) {

	    	$defaults = array( 
	    					'taxonomy' => 'category',
	    					'format' => 'select',
	    					'terms' => array()
	    				);

	    	$args = wp_parse_args($args, $defaults);

	    	$taxonomy = $args['taxonomy'];
	    	$format = $args['format'];
	    	$terms_list = $args['terms'];
	    	$selected_terms = array();

	    	if (isset($this->selected_taxonomies[$taxonomy])) {
	    		$selected_terms = $this->selected_taxonomies[$taxonomy];
	    	}

	    	$the_tax = get_taxonomy( $taxonomy );
	    	$tax_name = $the_tax->labels->name;
	    	$tax_slug = $the_tax->name;

	    	if (!$the_tax) {
				return;
			}

	    	if (isset($args['title'])) {
	    		$title = $args['title'];
	    	} else {
	    		$title = $tax_name;
	    	}

			$terms = array();

			if (count($terms_list) < 1) {
				$term_objects = get_terms($taxonomy, array( 'hide_empty' => 0 ));
				foreach ($term_objects as $term) {
					$terms[$term->term_id] = $term->name;
				}
			} else {
				foreach ($terms_list as $term_name) {
					$term = get_term_by('slug', $term_name, $taxonomy);
					if ($term) {
						$terms[$term->term_id] = $term->name;
					}
				}
			}

			$this->build_field('tax_'.$tax_slug, $format, $title, $terms, $selected_terms);

	    }


	    /**
		 * Generates a form field containing terms for a given meta key (custom field)
	     *
		 * @param array $args Arguments for configuring the field.
		 * @since 1.0
		 */
	    function meta_field( $args ) {

	    	$defaults = array(
	    					'title' => '',
	    					'meta_key' => '',
	    					'format' => 'select',
	    					'meta_values' => array()
	    				);

	    	$args = wp_parse_args($args, $defaults);

	    	$title = $args['title'];
	    	$meta_key = $args['meta_key'];
	    	$format = $args['format'];
	    	$meta_values = $args['meta_values'];
	    	$selected_values = array();

	    	if (isset($this->selected_meta_keys[$meta_key])) {
	    		$selected_values = $this->selected_meta_keys[$meta_key];
	    	}

	    	$this->build_field('meta_'.$meta_key, $format, $title, $meta_values, $selected_values);
	    }

	     /**
		 * Generates an author field
		 *
		 * @since 1.0
		 */   
	    function author_field( $args ) {
	    	$defaults = array(
					'title' => '',
					'format' => 'select',
					'authors' => array()
				);

	    	$args = wp_parse_args($args, $defaults);
	    	$title = $args['title'];
	    	$format = $args['format'];
	    	$authors_list = $args['authors'];
	    	$selected_authors = array();

	    	if (isset($this->selected_authors)) {
	    		$selected_authors = $this->selected_authors;
	    	}

	    	$the_authors_list = array();

			if (count($authors_list) < 1) {
					$authors = get_users();
					foreach ($authors as $author) {
						$the_authors_list[$author->ID] = $author->display_name;
					}
			} else {
				foreach ($authors_list as $author) {
					if (get_userdata($author)) {
						$user = get_userdata($author);
						$the_authors_list[$author] = $user->display_name;
					}
				}
			}

			$this->build_field('a', $format, $title, $the_authors_list, $selected_authors);

	    }

	     /**
		 * Generates a post type field
		 *
		 * @since 1.0
		 */   
	    function post_type_field( $args ) {
	    	$defaults = array(
					'title' => '',
					'format' => 'select',
					'values' => array()
				);

	    	$args = wp_parse_args($args, $defaults);
	    	$title = $args['title'];
	    	$format = $args['format'];
	    	$values = $args['values'];
	    	$selected_values = array();

	    	if (isset($this->selected_post_types)) {
	    		$selected_values = $this->selected_post_types;
	    	}

	    	$the_authors_list = array();

			if (count($values) < 1) {
				$post_types = get_post_types(); 
				foreach ( $post_types as $post_type ) {
					$obj = get_post_type_object($post_type);
					$post_type_id = $obj->name;
					$post_type_name = $obj->labels->name;
					$values[$post_type_id] = $post_type_name;
				}
			} 

			$this->build_field('ptype', $format, $title, $values, $selected_values);

	    }

	    function date_field( $args ) {
	    	$defaults = array(
			'title' => '',
			'format' => 'select',
			'date_type' => 'year',
			'values' => array() );

			extract(wp_parse_args($args, $defaults));

			$selected_values = array();
			$months = array(1 => 'January', 
							2 => 'Feburary', 
							3 => 'March', 
							4 =>'April', 
							5 => 'May', 
							6 => 'June', 
							7 => 'July', 
							8 => 'August', 
							9 => 'September', 
							10 => 'October', 
							11 => 'November', 
							12 => 'December');

			$days = array();
			for ($i=0;$i<31;$i++) {
				$days[$i + 1] = $i + 1;
			}

			switch ($date_type) {
				case ('year') :
					if (count($values) < 1) {
						$values = $this->get_years();
					}
					$selected_values = $this->selected_years;
					$id = 'date_y';
					break;
				case ('month') :
					if (count($values) < 1) {
						$values = $months;
					}
					$id = 'date_m';
					$selected_values = $this->selected_months;
					break;
				case ('day') :
					if (count($values) < 1) {
						$values = $days;
					}
					$id = 'date_d';
					$selected_values = $this->selected_days;
			}

			$this->build_field($id, $format, $title, $values, $selected_values);

	    }


	     /**
		 * Generates a form field
		 *
		 * @since 1.0
		 */
		 function build_field( $id, $format = 'select', $title = '', $values = array(), $selected_values = array() ) {
		 	switch ($format) {
		 		case ('select') :
		 			$this->select_field($id, $title, $values, $selected_values);
		 			break;
		 		case ('multi-select') :
		 			$this->select_field($id, $title, $values, $selected_values, true);
		 			break;
		 		case ('checkbox') :
		 			$this->checkbox_field($id, $title, $values, $selected_values);
		 			break;
		 		case ('radio') :
		 			$this->radio_field($id, $title, $values, $selected_values);
		 			break;
		 		case ('text') :
		 			$this->text_field($id, $title, $selected_values);
		 			break;
		 		case ('textarea') :
		 			$this->textarea_field($id, $title, $selected_values);
		 			break;

		 	}
		 }

		/**
		 * Builds a select field
		 *
		 * @since 1.0
		 */
	    function select_field( $id, $title, $values = array(), $selected = array(), $multi = false ) {
	    	$id = esc_attr($id);
	    	$title = esc_attr($title);

			if (is_string($selected)) {
	    		$selected = explode(',',$selected);
	    	}

	    	if ($multi) {
	    		$multiple = ' multiple="multiple"';
	    	} else {
	    		$multiple = '';
	    	}

	    	    echo '<div id="wpas-'.$id.'" class="wpas-field">';
				echo '<label for="select-'.$id.'">'.$title.' </label>';
				echo '<select id="select-'.$id.'" name="'.$id;
				if ($multi) {
					echo '[]';
				}
				echo  '"'.$multiple.'>';
				if (!$multi) {
					echo '<option value="">- select -</option>';
				}

				foreach ($values as $value => $label) {	
					$value = esc_attr($value);
					$label = esc_attr($label);
					echo '<option value="'.$value.'"';

						if (in_array($value, $selected)) {
							echo ' selected="selected"';
						}

					echo '>'.$label.'</option>';
				}

				echo '</select>';
				echo '</div>';

	    }

		/**
		 * Builds a checkbox field
		 *
		 * @since 1.0
		 */
	    function checkbox_field( $id, $title, $values = array(), $selected = array() ) {
	    	$id = esc_attr($id);
	    	$title = esc_attr($title);

		    	if (is_string($selected)) {
		    		$selected = explode(',',$selected);
		    	}

				echo '<div id="wpas-'.$id.'" class="wpas-field"><label for="wpas-'.$id.'-checkboxes">'.$title.' </label>';
				echo '<div class="wpas-'.$id.'-checkboxes">';
				foreach ($values as $value => $label) {
					$value = esc_attr($value);
					$label = esc_attr($label);
					echo '<div class="wpas-'.$id.'-checkbox"><input type="checkbox" name="'.$id.'[]" value="'.$value.'"';

						if (in_array($value, $selected)) {
							echo ' checked="checked"';
						}

					echo '>';

					echo '<label for="wpas-'.$id.'-checkbox"> '.$label.'</label></div>';
				}
				echo '</div></div>';
	    }

		/**
		 * Builds a radio button field
		 *
		 * @since 1.0
		 */
	    function radio_field( $id, $title, $values = array(), $selected = array() ) {
		    	$id = esc_attr($id);
		    	$title = esc_attr($title);

		    	if (is_string($selected)) {
		    		$selected = explode(',',$selected);
		    	}

				echo '<div id="wpas-'.$id.'" class="wpas-field"><label for="wpas-'.$id.'-radio-buttons">'.$title.' </label>';
				echo '<div class="wpas-'.$id.'-radio-buttons">';
				foreach ($values as $value => $label) {
					$value = esc_attr($value);
					$label = esc_attr($label);
					echo '<div class="wpas-'.$id.'-radio-button"><input type="radio" name="'.$id.'[]" value="'.$value.'"';

						if (in_array($value, $selected)) {
							echo ' checked="checked"';
						}

					echo '>';

					echo '<label for="wpas-'.$id.'-radio-button"> '.$label.'</label></div>';
				}
				echo '</div></div>';
	    }


		/**
		 * Builds the tax_query component of our WP_Query object based on form input
		 *
		 * @since 1.0
		 */
	    function build_tax_query() {
	    	$query = $this->wp_query_args;
	    	$taxonomies = $this->selected_taxonomies;

	    	foreach ($taxonomies as $tax => $terms) {
	    		$this->wp_query_args['tax_query'][] = array(	
				    									'taxonomy' => $tax,
														'field' => 'id',
														'terms' => $terms,
														'operator' => $this->taxonomy_operators[$tax]
														);
	    	}

	    }

		/**
		 * Builds the meta_query component of our WP_Query object based on form input
		 *
		 * @since 1.0
		 */
	    function build_meta_query() {
	    	$meta_keys = $this->selected_meta_keys;

	    	foreach ($meta_keys as $key => $values) {
	    		$this->wp_query_args['meta_query'][] = array(	
				    									'key' => $key,
														'value' => $values,
														'compare' => $this->meta_keys[$key]['compare'],
														'type' => $this->meta_keys[$key]['data_type']
														);
	    	}
	 
	    }


	    function _build_tax_query($return_simple = false) {
	    	$query = $this->wp_query_args;

	    	$taxonomies = get_object_taxonomies($query['post_type']);
	    	$tax_query = array();
	    	$the_terms = array();

	    	foreach ($_REQUEST as $request => $value) {

	    		if (taxonomy_exists($this->tax_from_arg($request))) {

	    			$taxonomy = $this->tax_from_arg($request);

	    			if ($return_simple) {

	    				if (is_array($value)) {
							foreach ($value as $term) {
									$the_terms[] =  $term;
							}
						} else {
		    				$the_terms[] = $value;
		    			}

	    			} else {

	    				$terms_list = array();

						if (is_array($value)) {
							foreach ($value as $term) {
									$terms_list[] =  $term;
							}
						} else {
		    				$terms_list[] = $value;
		    			}

						$tax_query[] =  array(	
		    									'taxonomy' => $taxonomy,
												'field' => 'id',
												'terms' => $terms_list,
												'operator' => $this->taxonomy_operators[$taxonomy]
										);
					}

				}

	    	}

	    	echo '<pre>';
	    	//print_r($args);
	    	echo '</pre>';

	    	if ($return_simple) {
	    		return $the_terms;
	    	}
	 
	    	return $tax_query;

	    }

		/**
		 * Processes form input and modifies the query accordingly
		 *
		 * @since 1.0
		 */
	    function process_form_input() {

	    	foreach ($_REQUEST as $request => $value) {

	    		if ($value) {

		    		if (substr($request, 0, 4) == 'tax_') {
		    			
		    			$tax = $this->tax_from_arg($request);
		    			if ($tax) {
		    				$this->selected_taxonomies[$tax] = $value;
		    			}

		    		} elseif (substr($request, 0, 5) == 'meta_') {

		    			$meta = $this->meta_from_arg($request);
		    			if ($meta) {
		    				$this->selected_meta_keys[$meta] = $value;
		    			}

		    		} else {
		    			$selected = array();
		    			if (!is_array($value)) {
							$selected[] = $value;
						} else {
							foreach ($value as $the_value) {
								$selected[] = $the_value;
							}
						}

		    			switch($request) {
		    				case('a') :
		    					$this->selected_authors = $selected;
		    					$this->wp_query_args['author'] = implode(',', $selected);
		    					break;
		    				case('ptype') :
		    					$this->selected_post_types = $selected;
		    					$this->wp_query_args['post_type'] = $selected;
		    					break;
		    				case('date_y') :
		    					$this->selected_years = $selected;
								$this->wp_query_args['year'] = implode(',', $selected);
		    					break;
		    				case('date_m') :
		    					$this->selected_months = $selected;
		    					$this->wp_query_args['monthnum'] = implode(',', $selected);
		    					break;    	
		    				case('date_d') :
		    					$this->selected_days = $selected;
		    					$this->wp_query_args['day'] = implode(',', $selected);
		    					break;	
		    				case('search_query') :
		    					$this->wp_query_args['s'] = implode(',', $selected);
		    					break;	
		    			}

		    

		    		}

		    	} // end if ($value)

	    	}// end foreach $_REQUEST

	    }


		/**
		 * Initializes a WP_Query object with the given search parameters
		 *
		 * @since 1.0
		 */
	    function query() {
	    	$this->build_tax_query();
	    	$this->build_meta_query();

	    	// Apply pagination
	    	if ( get_query_var('paged') ) {
			    $paged = get_query_var('paged');
			} else if ( get_query_var('page') ) {
			    $paged = get_query_var('page');
			} else {
			    $paged = 1;
			}
			$this->wp_query_args['paged'] = $paged;


	    	if (WPAS_DEBUG) {
	    		echo '<pre>';
	    		print_r($this->wp_query_args);
	    		echo '</pre>';
	    	}

	    	$this->wp_query = new WP_Query($this->wp_query_args);
	    	$query = $this->wp_query;
	    	return $query;
	    }

		function pagination( $args = '' ) {
			global $wp_query;
			$current_page = max(1, get_query_var('paged'));
			$total_pages = $wp_query->max_num_pages;

			if ( is_search() || is_post_type_archive() ) {  // Special treatment needed for search & archive pages
				$big = '999999999';
				$base = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
			} else {
				$base = get_pagenum_link(1) . '%_%';
			}

			$defaults = array(
							'base' => $base,
							'format' => 'page/%#%',
							'current' => $current_page,
							'total' => $total_pages
						);

			$args = wp_parse_args($args, $defaults);

			if ($total_pages > 1){
				echo '<div class="pagination">';
				echo paginate_links($args);
				echo '</div>';
			}

		}

		/**
		 * Template for displaying posts on the results page
		 *
		 * @since 1.0
		 */
		function post_template() {
	    	?>
	    	<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			<?php the_excerpt(); ?>

			<?php
	    }

		/**
		 * Accepts a term slug & taxonomy and returns the ID of that term
		 *
		 * @since 1.0
		 */
	    function term_slug_to_id( $term, $taxonomy ) {
	    	$term = get_term_by('slug', $term, $taxonomy);
	    	return $term->term_id;
	    }

	    /**
		 * Takes a specially-formatted taxonomy argument and returns the taxonomy name
		 *
		 * @since 1.0
		 */
	    function tax_from_arg( $arg ) {
	    	if (substr($arg, 0, 4) == 'tax_'){
	    		$tax = substr($arg, 4, strlen($arg) - 4);
	    		if (taxonomy_exists($tax)) {
	    			return $tax;
	    		} else {
	    			return false;
	    		}
	    	} else {
	    		return false;
	    	}

	    }

	    /**
		 * Takes a specially-formatted meta_key argument and returns the meta_key
		 *
		 * @since 1.0
		 */
	    function meta_from_arg( $arg ) {
	    	if (substr($arg, 0, 5) == 'meta_'){
	    		$meta = substr($arg, 5, strlen($arg) - 5);
	    		return $meta;
	    	} else {
	    		return false;
	    	}

	    }

	    function _print( $array ) {
	    	echo '<pre>';
	    	print_r($array);
	    	echo '</pre>';
	    }

	    /**
		 * Returns an array of years in which posts have been published
		 *
		 * @since 1.0
		 */
	    function get_years() {
	    	$post_type = $this->wp_query_args['post_type'];
	    	$posts = get_posts(array('numberposts' => -1, 'post_type' => $post_type));
	        $previous_year = "";

	        $display_format = "F Y";
	        $compare_format = "Ym";

	        $years = array();

	        foreach($posts as $post) {
	            $post_date = strtotime($post->post_date);
	            $current_year_month_display = date_i18n($display_format, $post_date);
	            $current_year_month_value = date($compare_format, $post_date);
	            $current_year = date("Y", $post_date);
	            //echo $current_year.'<br/>';
	            $current_month = date("m", $post_date);
	            if ($previous_year != $current_year) {
	            	if (empty($years[$current_year]))
	            		$years[$current_year] = $current_year;
	            }
	            $previous_year = $current_year;
	        }

	        return $years;

	    }

	} // class
} // endif

new WP_Advanced_Search();