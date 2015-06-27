<?php
namespace WPAS\Helper;
use WPAS\StdObject;

class ResultsRange extends StdObject {

    private function __construct() {}

    public static function make($query_object, $args) {
        $defaults = array(
            'pre' => '',
            'marker' => '-',
            'post' => ''
        );

        $args = self::parseArgs($args, $defaults);
        $range = self::computeRange($query_object, $args['marker']);

        return sprintf('<span>%s</span> <span>%s</span> <span>%s</span>', $args['pre'], $range, $args['post']);
    }

    private static function computeRange($query_object, $marker) {
        if ($query_object->found_posts < 1) return 0;

        $query = $query_object->query;
        $posts_per_page = (!empty($query['posts_per_page'])) ? $query['posts_per_page'] : get_option('posts_per_page');

        $current_page = (empty($query['paged'])) ? get_query_var('paged') : $query['paged'];

        if ($posts_per_page <= 1) return $current_page;

        $low = 1 + (($current_page - 1)*$posts_per_page);
        $high = $low + ($posts_per_page - 1);

        $range = sprintf('%d%s%d', $low, $marker, $high);
        if ($low >= $query_object->found_posts) {
            $range = $query_object->found_posts;
        }

        return $range;
    }
}