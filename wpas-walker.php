<?php
/**
 *  Class for building hierarchical lists of taxonomy terms
 *
 *  Derived from the native WordPress 'Walker'class.
 *
 *  @since 1.3
 */
class WPAS_Terms_Walker {

        private $taxonomy;
        private $term_identifier;
        private $elements;
        private $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

        function __construct( $args = array(), $term_args ) {
            $defaults = array(  'taxonomy' => 'category',
                                'term_format' => 'slug' );

            extract(wp_parse_args($args,$defaults));

            $this->taxonomy = $taxonomy;
            $this->term_identifier = $term_format;

            switch($term_format) {
                case 'id' :
                case 'ID' :
                     $this->term_identifier = 'term_id';
                    break;
                case 'Name' :
                case 'name' :
                     $this->term_identifier = 'name';
                    break;
                default :
                     $this->term_identifier = 'slug';
                    break;
            }

            $this->elements = get_terms($taxonomy, $term_args); 

        }

        /**
         *  Builds and returns a nested, hierarchical array of taxonomy terms
         */
        public function build_nested_terms_array( $max_depth ) {
                $parent_element_array = array();
                $value_array = array();

                if ($max_depth < -1) //invalid parameter
                        return $value_array;

                if (empty($this->elements)) //nothing to walk
                        return $value_array;

                $parent_field = $this->db_fields['parent'];

                // Max depth of -1 means no hierarchy
                if ( -1 == $max_depth ) {
                        $empty_array = array();
                        foreach ( $this->elements as $e ) {
                                $this->add_element( $e, $value_array, 
                                                    $parent_element_array, 
                                                    $empty_array, 1, 0);
                        }
                        return $value_array;
                }

                /*
                 * Separate elements into two buckets: top level and children elements.
                 * Children_elements is two dimensional array, eg.
                 * Children_elements[10][] contains all sub-elements whose parent is 10.
                 */
                $top_level_elements = array();
                $children_elements  = array();
                foreach ( $this->elements as $e) {
                        if ( 0 == $e->$parent_field )
                                $top_level_elements[] = $e;
                        else
                                $children_elements[ $e->$parent_field ][] = $e;
                }

                /*
                 * When none of the elements is top level.
                 * Assume the first one must be root of the sub elements.
                 */
                if ( empty($top_level_elements) ) {

                        $first = array_slice( $this->elements, 0, 1 );
                        $root = $first[0];

                        $top_level_elements = array();
                        $children_elements  = array();
                        foreach ( $this->elements as $e) {
                                if ( $root->$parent_field == $e->$parent_field )
                                        $top_level_elements[] = $e;
                                else
                                        $children_elements[ $e->$parent_field ][] = $e;
                        }
                }

                foreach ( $top_level_elements as $e )
                        $this->add_element( $e, $value_array, $parent_element_array, 
                                            $children_elements, $max_depth, 0);

                return $value_array;
        }


        /**
         *  Adds a term element and all of its children to $value_array.
         *  Recursively calls itself to add each of the term's children,
         *  incrementing depth by +1 in the process.
         */
        public function add_element($element, &$value_array, &$parent_element_array, 
                                    &$children_elements, $max_depth, $depth) {

            $id_field = $this->db_fields['id'];
            $id = $element->$id_field;
            $term_identifier = $this->term_identifier;

            $el = array(
                    'value' => $element->$term_identifier, 
                    'label' => $element->name,
                    'children' => array()
            );

            $has_children = false;
            if (!empty($children_elements) && !empty($children_elements[$id])) {
                $has_children = true;
            }

            if ( ($max_depth == 0 || $max_depth > $depth+1 ) && $has_children ) {
                foreach($children_elements[$element->term_id] as $child) {
                    $this->add_element($child, $value_array, $el, $children_elements, 
                                        $max_depth, $depth+1);
                }
            }

            if (empty($parent_element_array)) { 
                // If this is a top-level element, just append to the array
                $value_array[] = $el;  
            } else { 
                // Otherwise append to parent's children array
                $parent_element_array['children'][] = $el;
            }

        }

        /**
         *  Builds and returns a basic, non-hierarchical array of taxonomy
         *  terms.
         */
        public function build_basic_terms_array() {
            $term_values = array();
            $term_identifier = $this->term_identifier;

            foreach ($this->elements as $term) {
                $term_values[$term->$term_identifier] = $term->name;
            }

            return $term_values;
        }

}