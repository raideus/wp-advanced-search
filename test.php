<?php
//require('wpas-validator.php');
$class = ['one' => 'a','two' => 'b','three'];


$rules = array('four' => 'string', 'item' => 'string');
$data = array(
    'item' => 1,
    'item2' => "a string",
    'item3' => array("test"),
    'item4' => false,
    'four');


$subject = "array<something>";
$pattern = '/^array<?([a-zA-Z]*)>/';
$matches = "";
preg_match($pattern, $subject, $matches);

//$validator = new WPAS_Validator($rules, $data);
//echo ( is_object(array("hello")) ) ? "PASSED\n" : "FAILED\n";

//echo implode(' ', $class) . "\n";
