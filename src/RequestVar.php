<?php
namespace WPAS;
require_once('BasicEnum.php');

class RequestVar extends BasicEnum {
    const search = "search_query";
    const meta_key = "meta_";
    const taxonomy = "tax_";
    const date = "date_";
    const author = "a";
    const order = "order";
    const orderby = "orderby";
    const post_type = "ptype";
    const posts_per_page = "posts_per_page";
    const wpas = "wpas";

    public static function isValidValue($value) {
        return self::varToName($value);
    }

    public static function isValidDateVar($value) {
        $types = array('date_y'=>1,'date_m'=>1,'date_d'=>1);
        return (isset($types[$value]));
    }


    public static function varToName($value) {
        $values = array_values(self::getConstants());

        $prfx = substr($value, 0, 5);

        if ($prfx == "meta_") {
            return (strlen($value > 5))? substr($value, 5) : false;
        }

        if ($prfx == "date_") {
            if (self::isValidDateVar($value)) {
                return (strlen($value > 5))? substr($value, 5) : false;
            }
        }

        if (substr($prfx, 0, 4) == "tax_") {
            return (strlen($value > 4))? substr($value, 4) : false;
        }

        if (in_array($value, $values, $strict = true)) {
            return $value;
        }

        return false;
    }

    public static function nameToVar($input_name, $field_type = false) {
        switch($field_type) {
            case 'meta_key' :
                return self::meta_key . $input_name;
            case 'taxonomy' :
                return self::taxonomy . $input_name;
            case 'date' :
                return self::date . $input_name;
            default :
                return $input_name;
        }
    }



}