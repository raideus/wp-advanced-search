<?php
namespace WPAS\Enum;

class FieldType extends BasicEnum {
    const taxonomy = "taxonomy";
    const meta_key = "meta_key";
    const author = "author";
    const date = "date";
    const post_type = "post_type";
    const order = "order";
    const orderby = "orderby";
    const html = "html";
    const generic = "generic";
    const posts_per_page = "posts_per_page";
    const search = "search";
    const submit = "submit";
    const reset = "reset";
    const clear = "clear";

    public static function isQueryType($field_type) {
        return (!($field_type == self::submit || $field_type == self::generic || $field_type == self::html));
    }
}