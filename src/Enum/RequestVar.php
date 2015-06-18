<?php
namespace WPAS\Enum;

class RequestVar extends BasicEnum {
    const search = 'search_query';
    const meta_key = 'meta_';
    const taxonomy = 'tax_';
    const date = 'date_';
    const date_year = 'date_y';
    const date_month = 'date_m';
    const date_day = 'date_d';
    const author = 'a';
    const order = 'order';
    const orderby = 'orderby';
    const post_type = 'ptype';
    const posts_per_page = 'posts_per_page';
    const wpas = 'wpas';

    private static $wp_query_vars = array(
        'search' => 's',
        'post_type' => 'post_type',
        'author' => 'author',
        'meta_key' => 'meta_query',
        'taxonomy' => 'tax_query',
        'date' => 'date_query'
    );

    /**
     * Given a request variable name, returns the name (value) of that
     * variable.  Returns false if not a valid request var.
     *
     * e.g. 'meta_color' ==> 'color'
     *
     * @param $value
     * @return bool|string
     */
    public static function varToName($value) {
        $values = array_values(self::getConstants());

        if (self::isMetaVar($value) || self::isDateVar($value)) {
            return (strlen($value > 5))? substr($value, 5) : false;
        }

        if (self::isTaxVar($value)) {
            return (strlen($value > 4))? substr($value, 4) : false;
        }

        if (in_array($value, $values, $strict = true)) {
            return $value;
        }

        return false;
    }

    /**
     * Given an input name and optional field type, returns the corresponding
     * request parameter name used by WPAS.
     *
     * e.g. color, meta_key  ==> 'meta_color'
     *
     * @param $input_name
     * @param $field_type
     * @return string
     */
    public static function nameToVar($input_name, $field_type = false, $date_type = false) {
        switch($field_type) {
            case 'meta_key' :
                return self::meta_key . $input_name;
            case 'taxonomy' :
                return self::taxonomy . $input_name;
            case 'date' :
                if (!$date_type) return self::date_year;
                return self::date . self::dateTypeSuffix($date_type);
        }

        if (self::isValidName($input_name)) return constant( 'self::'. $input_name );
        return $input_name;
    }

    /**
     * Given a request var, returns the equivalent variable name for WP_Query.
     * Returns false if not a valid request var.
     *
     * @param RequestVar $request_var
     * @return bool|string
     */
    public static function wpQueryVar($request_var) {
        if (!self::isValidName($request_var)) return false;

        if (!empty(self::$wp_query_vars[$request_var])) {
            return self::$wp_query_vars[$request_var];
        }

        return constant( 'self::'. $request_var );
    }

    private static function dateTypeSuffix($date_type) {
        switch($date_type) {
            case 'month' :
                return 'm';
            case 'day' :
                return 'd';
            default :
                return 'y';
        }
    }

    public static function isValidValue($value) {
        return self::varToName($value);
    }

    public static function isValidDateVar($value) {
        $types = array('date_y'=>1,'date_m'=>1,'date_d'=>1);
        return (isset($types[$value]));
    }

    public static function isTaxVar($value) {
        return (substr($value, 0, 4) == self::taxonomy);
    }

    public static function isMetaVar($value) {
        return (substr($value, 0, 5) == self::meta_key);
    }

    public static function isDateVar($value) {
        return (substr($value, 0, 5) == self::date);
    }



}