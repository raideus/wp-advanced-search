<?php
namespace WPAS\Helper;
use WPAS\StdObject;

class Pagination extends StdObject {

    private function __construct() {}

    public static function make($query_object, $args = array(), $ajax_enabled = false) {

        if ($ajax_enabled) {
            $base = "#";
        } else {
            $base = str_replace( '9999999', '%#%', get_pagenum_link( '9999999' ) );
        }

        $defaults = array(
            'base' => $base,
            'format' => 'page/%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $query_object->max_num_pages
        );

        $args = self::parseArgs($args, $defaults);
        $output = self::createOutput($query_object, $args);

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