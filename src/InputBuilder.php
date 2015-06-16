<?php
namespace WPAS;
use WPAS\Enum\FieldType;

class InputBuilder extends StdObject {

    private function __construct() {}

    /**
     * Initializes and returns an Input object according to the
     * given field type and arguments
     *
     * @param string $input_name
     * @param string $field_type
     * @param array  $args
     * @return object
     */
    public static function make($input_name, $field_type, $args,
                                $request = false) {

        self::validateFieldType($field_type);
        $args = self::preProcess($input_name, $field_type, $args, $request);
        $args = call_user_func("self::$field_type", $input_name, $args,$request);
        $args = self::postProcess($input_name, $field_type, $args, $request);

        return new Input($input_name, $args);
    }

    /**
     * Validate the input arguments
     *
     * @param $field_type
     */
    protected static function validateFieldType($field_type) {
        if (FieldType::isValid($field_type)) return;
        $err_msg = self::validationErrorMsg(
            array('Argument 1 `$field_type` ' .
                'must be a valid FieldType.'));
        throw new \InvalidArgumentException($err_msg);
    }

    /**
     * Pre-processing of input arguments
     *
     * @param $input_name
     * @param $field_type
     * @param $args
     * @param $request
     * @return mixed
     */
    protected static function preProcess($input_name, $field_type, $args,
                                       $request) {

        if (isset($args['exclude']) && is_scalar($args['exclude'])) {
            $args['exclude'] = array($args['exclude']);
        }

        if (isset($args['class']) && is_string($args['class'])) {
            $args['class'] = explode(',', $args['class']);
        }

        if (!isset($args['values']) && isset($args['value'])) {
            $args['values'] = array($args['value']);
        }

        $args['field_type'] = $field_type;

        return $args;
    }

    /**
     * Post-processing of input arguments
     *
     * @param $input_name
     * @param $field_type
     * @param $args
     * @param $request
     * @return mixed
     */
    protected static function postProcess($input_name, $field_type, $args,
                                            $request) {
        $args['selected'] = self::getSelected($input_name, $field_type, $args,
                                              $request);
        $args = self::removeExcludedValues($args);
        return $args;
    }

    /**
     * Removes values from the 'values' array which are flagged under the
     * 'exclude' option
     *
     * @param $args
     * @return mixed
     */
    protected static function removeExcludedValues($args) {
        if (empty($args['values']) || empty($args['exclude'])) return $args;

        if (isset($args['exclude']) && is_scalar($args['exclude'])) {
            $args['exclude'] = array($args['exclude']);
        } else if (!is_array($args['exclude'])) {
            $args['exclude'] = array();
        }

        foreach ($args['exclude'] as $e) {
            if (isset($args['values'][$e])) unset($args['values'][$e]);
        }

        return $args;

    }

    /**
     * Returns an array of selected input elements based on the given
     * arguments and request data.
     *
     * @param $input_name
     * @param $field_type
     * @param $args
     * @param $request
     * @return array
     */
    protected static function getSelected($input_name, $field_type, $args,
                                       $request) {
        $request_var = $input_name;

        $request_val = isset( $request[$request_var] ) ? $request[$request_var] : null;

        if (isset($request_val)) {
            if (is_array($request_val)) {
                return $request_val ;
            }
            return array($request_val);
        }

        if (self::canApplyDefaultAll($args, $request)) {
            $selected = array();
            foreach ($args['values'] as $value => $label) {
                $selected[] = $value;
            }
            return $selected;
        }


        if (isset($args['default']) && !isset($request['wpas_submit'])) {
            if (!is_array($args['default'])) {
                return array($args['default']);
            }
            return $args['default'];
        }

        return array();
    }

    /**
     * Determine whether the default_all option should be invoked under
     * the given arguments and request data
     *
     * @param $args
     * @param $request
     * @return bool
     */
    protected static function canApplyDefaultAll($args, $request) {
        $format = isset($args['format']) ? $args['format'] : false;
        $default_all = isset($args['default_all']) ? $args['default_all'] : false;
        $supports_multiple = ($format == 'checkbox' || $format == 'multi-select');

        return ($default_all && $supports_multiple && !isset($request['wpas_submit']));
    }

    /**
     * Generates a search field
     */
    public static function search($input_name, $args, $request) {
        $defaults = array(
            'label' => '',
            'format' => 'text',
            'values' => array()
        );
        $args = self::parseArgs($args, $defaults);

        return $args;
    }

    /**
     * Generates a submit button
     */
    public static function submit($input_name, $args, $request) {
        $defaults = array(
            'values' => array('Search')
        );
        $args = self::parseArgs($args, $defaults);
        $args['format'] = 'submit';
        return $args;
    }

    /**
     * Generates a reset button
     */
    public static function reset($input_name, $args, $request) {
        $defaults = array(
            'values' => array('Reset')
        );
        $args = self::parseArgs($args, $defaults);
        $args['format'] = 'reset';
        return $args;
    }

    /**
     * Generates a clear button
     */
    public static function clear($input_name, $args, $request) {
        $defaults = array(
            'values' => array('Clear')
        );
        $args = self::parseArgs($args, $defaults);
        $args['format'] = 'clear';
        return $args;
    }

    /**
     * Configure a meta_key input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function meta_key($input_name, $args, $request) {
        $defaults = array(
            'label' => '',
            'format' => 'select',
            'data_type' => 'CHAR',
            'compare' => 'IN',
            'values' => array()
        );
        $args = self::parseArgs($args, $defaults);
        return $args;
    }

    /**
     * Configure an order input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function order($input_name, $args, $request) {
        $defaults = array(
            'label' => '',
            'format' => 'select',
            'values' => array('ASC' => 'ASC', 'DESC' => 'DESC')
        );

        $args = self::parseArgs($args, $defaults);
        return $args;
    }

    /**
     * Configure an orderby input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function orderby($input_name, $args, $request) {
        $defaults = array(
                        'label' => '',
                        'format' => 'select',
                        'values' => array(  'ID' => 'ID',
                                            'author' => 'Author',
                                            'title' => 'Title',
                                            'date' => 'Date',
                                            'modified' => 'Modified',
                                            'parent' => 'Parent ID',
                                            'rand' => 'Random',
                                            'comment_count' => 'Comment Count',
                                            'menu_order' => 'Menu Order' )
                        );

        if (isset($args['orderby_values']) && is_array($args['orderby_values'])) {
            $args['values'] = array(); // orderby_values overrides normal values
            foreach ($args['orderby_values'] as $k=>$v) {
                if (isset($v['label'])) $label = $v['label'];
                else $label = $k;
                $args['values'][$k] = $label; // add to the values array
            }
        }
        $args = self::parseArgs($args, $defaults);
        return $args;
    }

    /**
     * Configure an author input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function author($input_name, $args, $request) {
        $defaults = array(
            'label' => '',
            'format' => 'select',
            'authors' => array()
        );
        $args = self::parseArgs($args, $defaults);


        $authors_list = $args['authors'];

        $the_authors_list = array();

        if (count($authors_list) < 1) {
            $authors = get_users(array('who' => 'authors'));
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

        $args['values'] = $the_authors_list;
        return $args;
    }

    /**
     * Configure a post_type input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function post_type($input_name, $args, $request) {
        $defaults = array(
            'label' => '',
            'format' => 'select',
            'values' => array('post' => 'Post', 'page' => 'Page')
        );
        $args = self::parseArgs($args, $defaults);
        $values = $args['values'];

        if (count($values) < 1) {
            $post_types = get_post_types(array('public' => true));
            foreach ( $post_types as $post_type ) {
                $obj = get_post_type_object($post_type);
                $post_type_id = $obj->name;
                $post_type_name = $obj->labels->name;
                $values[$post_type_id] = $post_type_name;
            }
        }

        $args['values'] = $values;
        return $args;
    }

    /**
     * Configure an html input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function html($input_name, $args, $request) {
        $defaults = array(
            'label' => '',
            'values' => array()
        );
        $args = self::parseArgs($args, $defaults);
        $args['format'] = 'html';

        return $args;
    }

    /**
     * Configure a generic input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function generic($input_name, $args, $request) {
        return $args;
    }

    /**
     * Configure a posts_per_page input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @return array
     */
    public static function posts_per_page($input_name, $args, $request) {
        $defaults = array(
            'format' => 'select',
            'values' => array(10 => "10", 25 => "25", 50 => "50")
        );
        $args = self::parseArgs($args, $defaults);
        return $args;
    }

    /**
     * Configure a taxonomy input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @throws \Exception
     * @return array
     */
    public static function taxonomy($input_name, $args, $request) {
        $defaults = array(
            'label' => '',
            'format' => 'select',
            'term_format' => 'slug',
            'operator' => 'AND',
            'hide_empty' => false,
            'terms' => array(),
            'nested' => false,
            'term_args' => array()
        );

        $term_defaults = array(
            'hide_empty' => false
        );

        extract(self::parseArgs($args, $defaults));

        $the_tax = get_taxonomy($taxonomy);

        if (!is_object($the_tax)) {
            $msg = "Taxonomy '". $taxonomy ."' not found in this WordPress
            installation.";
            throw new \Exception($msg);
        }

        if (isset($term_args) && is_array($term_args)) {
            $term_args = self::parseArgs($term_args, $term_defaults);
        }

        if (isset($terms) && is_array($terms) && (count($terms) < 1)) {
            // No terms specified; populate with all terms
            $walker = new TermsWalker(array('taxonomy' => $taxonomy,
                'term_format' => $term_format),
                $term_args);
            if ($nested) {
                $term_values = $walker->build_nested_terms_array(0);
            } else {
                $term_values = $walker->build_basic_terms_array();
            }
        } else { // Custom term list
            $args['nested'] = false; // Disallow nesting for custom term lists
            $term_values = self::customTermsList($terms, $taxonomy, $term_format);
        }

        if (empty($values)) {
            // Populate with values unless this is a text or textarea field
            if (!($format == 'text' || $format == 'textarea')) {
                $args['values'] = $term_values;
            }
        }

        return $args;
    }

    private static function customTermsList($terms, $taxonomy, $term_format) {
        $term_objects = array();
        $term_values = array();
        foreach ($terms as $term_identifier) {
            $term = get_term_by($term_format, $term_identifier, $taxonomy);
            if ($term) {
                $term_objects[] = $term;
            }
        }
        foreach ($term_objects as $term) {
            $term_values[self::termValue($term,$term_format)] = $term->name;
        }
        return $term_values;
    }

    private static function termValue($term, $format) {
        switch(strtolower($format)) {
            case 'id' :
                return $term->term_id;
            case 'name' :
                return $term->name;
            default :
                return $term->slug;
        }
    }

}