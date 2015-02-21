<?php
/**
 */

namespace WPAS;
require_once('BasicEnum.php');

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
}