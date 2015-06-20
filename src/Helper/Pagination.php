<?php
namespace WPAS\Helper;
use WPAS\StdObject;

class Pagination extends StdObject {

    private function __construct() {}

    public static function make($query_object, $args = array(), $ajax_enabled = false) {
        global $wp_query;
        $temp = $wp_query;
        $wp_query = $query_object;

        if ($ajax_enabled) {
            $base = "#";
        } else {
            $base = str_replace( '2', '%#%', get_pagenum_link( '2' ) );
        }

        $defaults = array(
            'base' => $base,
            'format' => 'page/%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $wp_query->max_num_pages
        );

        $args = self::parseArgs($args, $defaults);
        $output = self::createOutput($wp_query, $args);

        $wp_query = $temp;

        return $output;
    }

    private static function createOutput($query_object, $args) {
        $output = "";

        if ($query_object->max_num_pages > 1) {
            $output .=  '<div class="pagination">';
            $output .= paginate_links($args);
            $output .=  '</div>';
        }

        return $output;
    }

}