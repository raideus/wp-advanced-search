<?php
/**
 *  Abstract Enum class
 *
 *  @author Brian Cline
 *  @link http://stackoverflow.com/a/254543
 */

namespace WPAS;
require_once('BasicEnum.php');

class InputFormat extends BasicEnum {
    const select = "select";
    const multi_select = "multi-select";
    const checkbox = "checkbox";
    const radio = "radio";
    const text = "text";
    const hidden = "hidden";
    const number = "number";
    const color = "color";
    const url = "url";
    const email = "email";
    const tel = "tel";
    const date = "date";
    const datetime = "datetime";
    const time = "time";
    const week = "week";
}