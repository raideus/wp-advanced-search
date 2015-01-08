<?php

require_once(ABSPATH . '/wp-includes/class-wp-walker.php');

class WPAS_Walker extends Walker {

        public $tree_type = 'category';
        public $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); 

        public function build_nested_array( $elements, $max_depth ) {

                $args = array_slice(func_get_args(), 2);
                $output = '';

                $parent_element_array = array();
                $value_array = array();

                if ($max_depth < -1) //invalid parameter
                        return $output;

                if (empty($elements)) //nothing to walk
                        return $output;

                $parent_field = $this->db_fields['parent'];

                // flat display
                if ( -1 == $max_depth ) {
                        $empty_array = array();
                        foreach ( $elements as $e )
                                $this->add_element( $e, $value_array, $parent_element_array, $empty_array, 1, 0, $args);
                        return $output;
                }

                /*
                 * Separate elements into two buckets: top level and children elements.
                 * Children_elements is two dimensional array, eg.
                 * Children_elements[10][] contains all sub-elements whose parent is 10.
                 */
                $top_level_elements = array();
                $children_elements  = array();
                foreach ( $elements as $e) {
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

                        $first = array_slice( $elements, 0, 1 );
                        $root = $first[0];

                        $top_level_elements = array();
                        $children_elements  = array();
                        foreach ( $elements as $e) {
                                if ( $root->$parent_field == $e->$parent_field )
                                        $top_level_elements[] = $e;
                                else
                                        $children_elements[ $e->$parent_field ][] = $e;
                        }
                }

                foreach ( $top_level_elements as $e )
                        $this->add_element( $e, $value_array, $parent_element_array, $children_elements, $max_depth, 0, $args);


                return $value_array;
        }


        public function add_element($element, &$value_array, &$parent_element_array, &$children_elements, $max_depth, $depth, $args) {

            $id_field = $this->db_fields['id'];
            $id = $element->$id_field;

            $el = array(
                    'id' => $id, 
                    'children' => array()
            );

            $has_children = false;
            if (!empty($children_elements) && !empty($children_elements[$id])) {
                $has_children = true;
            }

            if ( ($max_depth == 0 || $max_depth > $depth+1 ) && $has_children ) {
                foreach($children_elements[$element->term_id] as $child) {
                    $this->add_element($child, $value_array, $el, $children_elements, $max_depth, $depth+1, $args);
                }
            }

            if (empty($parent_element_array)) { // If this is a top-level element, just append to the array
                $value_array[$el['id']] = $el;  
            } else { // Otherwise append to parent's children array
                $parent_element_array['children'][$el['id']] = $el;
            }

        }


}