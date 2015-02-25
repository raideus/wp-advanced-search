<?php
/**
 * Created by PhpStorm.
 * User: sean
 * Date: 2/23/15
 * Time: 9:11 PM
 */

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
        $values = array_values(self::getConstants());
        $prfx = substr($value, 0, 5);

        if ($prfx == "meta_") {
            return strlen($value > 5);
        }
        if ($prfx == "date_") {
            return self::isValidDateVar($value);
        }
        if (substr($prfx, 0, 4) == "tax_") {
            return strlen($value > 4);
        }
        return in_array($value, $values, $strict = true);
    }

    public static function isValidDateVar($value) {
        $types = array('date_y'=>1,'date_m'=>1,'date_d'=>1);
        return (isset($types[$value]));
    }

}