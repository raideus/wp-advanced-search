<?php
namespace WPAS;
require_once(dirname(__DIR__) . '/wpas.php');

class TestType extends \PHPUnit_Framework_TestCase {

    public function testPassesOnValidTypes() {
        $types = array(
            "string",
            "numeric",
            "bool",
            "scalar",
            "array",
            "object"
        );

        foreach ($types as $type) {
            $this->assertTrue(Type::isValidName($type));
        }

    }

    public function testFailsOnInvalidTypes() {
         $this->assertFalse(Type::isValidName("badtype"));
         $this->assertFalse(Type::isValidName(1));
         $this->assertFalse(Type::isValidName(array("badtype")));
    }


    public function testPassesOnValidArrayTypes() {
        $types = array(
            "string",
            "numeric",
            "bool",
            "scalar",
            "array",
            "object"
        );

        foreach ($types as $type) {
            $this->assertTrue(Type::isValidName("array<".$type.">"));
        }
    }

    public function testFailsOnInvalidArrayType() {
         $this->assertFalse(Type::isValidName("array<badtype>"));
    }

    public function testTypeSubsets() {
        $this->assertTrue(Type::isSubtypeOf("string", "string"));
        $this->assertTrue(Type::isSubtypeOf("string", "scalar"));
        $this->assertTrue(Type::isSubtypeOf("numeric", "scalar"));
        $this->assertTrue(Type::isSubtypeOf("bool", "scalar"));

        $this->assertFalse(Type::isSubtypeOf("array", "scalar"));
        $this->assertFalse(Type::isSubtypeOf("object", "scalar"));

        $this->assertTrue(Type::isSubtypeOf("array<string>", "array"));
        $this->assertTrue(Type::isSubtypeOf("array<scalar>", "array"));
        $this->assertTrue(Type::isSubtypeOf("array<string>", "array<scalar>"));
        $this->assertTrue(Type::isSubtypeOf("array<numeric>", "array<numeric>"));

        $this->assertFalse(Type::isSubtypeOf("array<scalar>", "array<string>"));
        $this->assertFalse(Type::isSubtypeOf("array<numeric>", "array<string>"));

    }

    public function testMatches() {

         $this->assertTrue(Type::matches("string", "hello"));
         $this->assertTrue(Type::matches("bool", true));
         $this->assertTrue(Type::matches("numeric", 134));
         $this->assertTrue(Type::matches("object", $this));
         $this->assertTrue(Type::matches("RequestMethod", "GET"));
         $this->assertTrue(Type::matches("RequestMethod", "get"));
         $this->assertTrue(Type::matches("RequestMethod", "POST"));
         $this->assertTrue(Type::matches("InputFormat", "multi-select"));
         $this->assertTrue(Type::matches("array", array(1, "five")));
         $this->assertTrue(Type::matches("array<string>", array("one", "five")));
         $this->assertTrue(Type::matches("array<bool>", array(true, false)));
         $this->assertTrue(Type::matches("array<numeric>", array(5, 98)));
         $this->assertTrue(Type::matches("array<scalar>", array("one", 98, false)));
         $this->assertTrue(Type::matches("array<object>", array($this)));

         $this->assertFalse(Type::matches("string", 431));
         $this->assertFalse(Type::matches("bool", "hello"));
         $this->assertFalse(Type::matches("numeric", array(123)));
         $this->assertFalse(Type::matches("array", 1));
         $this->assertFalse(Type::matches("RequestMethod", "BADMETHOD"));
         $this->assertFalse(Type::matches("InputFormat", "notaformat"));
         $this->assertFalse(Type::matches("array<string>", array(1, "five", $this)));
         $this->assertFalse(Type::matches("array<bool>", array("hi", false)));
         $this->assertFalse(Type::matches("array<numeric>", array(5, "one")));
         $this->assertFalse(Type::matches("array<scalar>", array("one", array(98), false)));
         $this->assertFalse(Type::matches("array<object>", array("one", array(98), false)));

    }

    public function testFlexibleBoolType() {
        $this->assertTrue(Type::matches("bool", "1"));
        $this->assertTrue(Type::matches("bool", "0"));
        $this->assertTrue(Type::matches("bool", "true"));
        $this->assertTrue(Type::matches("bool", "false"));
    }

}