<?php
namespace WPAS\Parser;
use WPAS\StdObject;
use WPAS\TermsWalker;

class TaxonomyArgParser extends StdObject implements InputArgParser {

    protected static $defaults = array(
        'label' => '',
        'format' => 'select',
        'term_format' => 'slug',
        'operator' => 'AND',
        'hide_empty' => false,
        'terms' => array(),
        'nested' => false,
        'taxonomy' => null,
        'term_args' => array()
    );

    protected static $term_defaults = array(
        'hide_empty' => false
    );

    public static function parse(array $args) {
        $args = self::parseArgs($args, self::$defaults);

        $the_tax = get_taxonomy($args['taxonomy']);

        if (!is_object($the_tax)) {
            $msg = "Taxonomy '". $args['taxonomy'] ."' not found in this WordPress
            installation.";
            throw new \Exception($msg);
        }

        $args = self::setTermArgs($args);
        $format = $args['format'];

        if (!empty($args['values']) || $format == 'text' || $format == 'textarea') {
            return $args;
        }

        $args = self::addTermsList($args);

        return $args;
    }

    private static function setTermArgs($args) {
        if (isset($args['term_args']) && is_array($args['term_args'])) {
            $args['term_args'] = self::parseArgs($args['term_args'], self::$term_defaults);
        }
        return $args;
    }

    private static function addTermsList($args) {
        extract($args);
        if (isset($terms) && is_array($terms) && (count($terms) < 1)) {
            // No terms specified; populate with all terms
            $args['values'] = self::allTermsList($taxonomy, $term_args, $term_format, $nested);
        } else { // Custom term list
            $args['nested'] = false; // Disallow nesting for custom term lists
            $args['values'] = self::customTermsList($terms, $taxonomy, $term_format);
        }
        return $args;
    }

    private static function allTermsList($taxonomy, $term_args, $term_format, $nested) {
        $walker = new TermsWalker(array('taxonomy' => $taxonomy,
            'term_format' => $term_format),
            $term_args);
        if ($nested) {
            return $walker->build_nested_terms_array(0);
        }
        return $walker->build_basic_terms_array();
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