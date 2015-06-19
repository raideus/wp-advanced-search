<?php
namespace WPAS;
use WPAS\InputBuilder;
use WPAS\Enum\FieldType;

class DateInputBuilder extends InputBuilder {

    private function __construct() {}

    /**
     * Initializes and returns an Input object according to the
     * given field type and arguments
     *
     * @param string $input_name
     * @param array  $args
     * @param array  $post_types
     * @return object
     */
    public static function make($input_name, $args, $post_types, $request = false) {
        $args = self::preProcess(FieldType::date, $args);
        $args = self::addDateValues($args, $post_types);
        $args = self::configure($input_name, $args,$request);
        $args = self::postProcess($input_name, FieldType::date, $args, $request);

        return new Input($input_name, $args);
    }

    /**
     * Configure the date input
     *
     * @param $input_name
     * @param $args
     * @param $request
     * @throws \Exception
     * @return array
     */
    public static function configure($input_name, $args, $request) {
        $default_date_type = 'year';
        $defaults = array(
            'label' => '',
            'format' => 'select',
            'date_type' => $default_date_type,
            'date_format' => false,
            'values' => array() );
        $disallowed_formats = array('multi-select', 'checkbox');

        $args = self::parseArgs($args, $defaults);

        if (in_array($args['format'], $disallowed_formats)) {
            throw new \Exception("Date field does not currently support multi-select or checkbox formats");
        }

        return $args;
    }

    /**
     * Add auto-generated list of dates to an arguments array
     *
     * @param $args
     * @param array $post_types
     * @return mixed
     */
    private static function addDateValues($args, array $post_types) {
        if (!empty($args['values'])) return $args;

        $date_format = (empty($args['date_format'])) ? false : $args['date_format'];
        $date_type = (empty($args['date_type'])) ? 'year' : $args['date_type'];

        $args['values'] = self::getDates($date_type, $date_format, $post_types);
        return $args;
    }


    /**
     * Returns an array of dates in which content has been published
     *
     * @param string $date_type
     * @param bool $format
     * @param array $post_types
     * @return array
     */
    private static function getDates($date_type = 'year', $format = false, array $post_types) {
        $display_format = ($format) ? $format : self::dateFormat('display', $date_type);
        $compare_format = self::dateFormat('compare', $date_type);

        $post_status = 'publish';
        $posts = get_posts(array('numberposts' => 1000, 'post_type' => $post_types, 'post_status' => $post_status));

        $previous_value = "";
        $dates = array();

        foreach($posts as $post) {
            $post_date = strtotime($post->post_date);
            $current_display = date_i18n($display_format, $post_date);
            $current_value = date($compare_format, $post_date);
            if ($previous_value != $current_value) {
                $dates[$current_value] = $current_display;
            }
            $previous_value = $current_value;

        }
        return $dates;
    }


    private static function dateFormat($format, $date_type) {
        $map = array(
            'display' => array('year' => 'Y', 'month' =>'M Y', 'day' => 'M j, Y'),
            'compare' => array('year' => 'Y', 'month' =>'Y-m', 'day' => 'Y-m-d')
        );

        return (isset($map[$format][$date_type])) ? $map[$format][$date_type] : reset($map[$format]);
    }

}