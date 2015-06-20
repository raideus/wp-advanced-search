<?php
namespace WPAS\Helper;
use WPAS\StdObject;

class ResultsRange extends StdObject {

    private function __construct() {}

    public static function make($args) {
        $defaults = array(
            'pre' => '',
            'marker' => '-',
            'post' => ''
        );

        $args = self::parseArgs($args, $defaults);
        $range = self::computeRange($args['marker']);

        return sprintf('<span>%s</span> <span>%s</span> <span>%s</span>', $args['pre'], $range, $args['post']);
    }

    private static function computeRange( $marker ) {
        global $wp_query;

        if ($wp_query->found_posts < 1) return 0;

        $query = $wp_query->query;
        $posts_per_page = (!empty($query['posts_per_page'])) ? $query['posts_per_page'] : get_option('posts_per_page');
        $current_page = get_query_var('paged');

        if ($posts_per_page <= 1) return $current_page;

        $low = 1 + (($current_page - 1)*$posts_per_page);
        $high = $low + ($posts_per_page - 1);

        $range = sprintf('%d%s%d', $low, $marker, $high);
        if ($low > $wp_query->found_posts) {
            $range = $wp_query->found_posts;
        }

        return $range;
    }
}