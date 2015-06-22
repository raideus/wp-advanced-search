<?php
namespace WPAS;

/**
 *  Class for building hierarchical lists of taxonomy terms
 *
 *  Derived from the native WordPress 'Walker'class.
 *
 */
class TermsWalker extends StdObject {

    private $taxonomy;
    private $term_identifier;
    private $elements;
    private static $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

    function __construct( $args = array(), $term_args ) {
        $defaults = array(  'taxonomy' => 'category',
            'term_format' => 'slug' );

        extract($this->parseArgs($args,$defaults));

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
     *
     *  @param int $max_depth The maximum number of nested levels to recurse
     *                        (0 == all levels, -1 == flat hierarchy)
     *  @return array An Array of nested taxonomy terms
     */
    public function build_nested_terms_array( $max_depth ) {
        $parent_field = self::$db_fields['parent'];
        $parent_element_array = array();
        $value_array = array();

        if ($max_depth < -1) //invalid parameter
            return $value_array;

        if (empty($this->elements)) //nothing to walk
            return $value_array;

        // Max depth of -1 means no hierarchy
        if ( -1 == $max_depth ) {
            return $this->build_basic_terms_array();
        }

        $elements_table = array(0 => array());
        foreach ( $this->elements as $e) {
           $elements_table[ $e->$parent_field ][] = $e;
        }

        foreach ( $elements_table[0] as $e ) {
            $this->add_element($e, $value_array, $parent_element_array,
                $elements_table, $max_depth, 0);
        }

        return $value_array;
    }


    /**
     *  Adds a term element and all of its children to $value_array.
     *  Recursively calls itself to add each of the term's children,
     *  incrementing depth by +1 in the process.
     *
     *  @param array $element Term element to be added to the array
     *  @param array $value_array  Master array to add the element to
     *  @param array $parent_element_array  Parent term of the current element
     *  @param array $elements_table  Table of all elements index by parent_id
     *  @param int   $max_depth  The maximum number of nested levels to recurse
     *  @param int   $depth  The current depth of recursion
     */
    public function add_element($element, &$value_array, &$parent_element_array,
                                &$elements_table, $max_depth, $depth) {

        $id_field = self::$db_fields['id'];
        $id = $element->$id_field;
        $term_identifier = $this->term_identifier;

        $el = array(
            'value' => $element->$term_identifier,
            'label' => $element->name,
            'children' => array()
        );

        $has_children = false;
        if (!empty($elements_table) && !empty($elements_table[$id])) {
            $has_children = true;
        }

        if ( ($max_depth == 0 || $max_depth > $depth+1 ) && $has_children ) {
            foreach($elements_table[$element->term_id] as $child) {
                $this->add_element($child, $value_array, $el, $elements_table,
                    $max_depth, $depth+1);
            }
        }

        if (empty($parent_element_array)) {
            // If this is a top-level element, just append to the array
            $value_array[$el['value']] = $el;
        } else {
            // Otherwise append to parent's children array
            $parent_element_array['children'][$el['value']] = $el;
        }

    }

    /**
     *  Builds and returns a basic, non-hierarchical array of taxonomy
     *  terms.
     *
     *  @return array
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