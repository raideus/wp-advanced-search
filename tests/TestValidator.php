<?php
namespace WPAS;
require_once(dirname(__DIR__) . '/wpas.php');

class TestValidator extends \PHPUnit_Framework_TestCase {

    public function testPassesWithNoRules() {
        $rules = array();
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"),
            'item4' => false);

        $validation = new Validator($rules, $data);
        $this->assertTrue($validation->passes());
    }

    public function testPassesWithNoData() {
        $data = array();
        $rules = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"),
            'item4' => false);

        $validation = new Validator($rules, $data);
        $this->assertTrue($validation->passes(), $validation->getErrors());
    }

    public function testPassesWithMissingArgValue() {
        $rules = array('item4' => 'string');
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"),
            'item4');

        $validation = new Validator($rules, $data);
        $this->assertTrue($validation->passes());
    }

    public function testFailsWithMissingRequiredValue() {
        $rules = array('item4' => array('type' => 'string', 'required' => true));
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"),
            'item4');

        $validation = new Validator($rules, $data);
        $this->assertFalse($validation->passes());

        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"));
        $validation = new Validator($rules, $data);
        $this->assertFalse($validation->passes());
    }

    public function testPassesWithMissingNonRequiredValue() {
        $rules = array('item4' => array('type' => 'string', 'required' => false));
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"));

        $validation = new Validator($rules, $data);
        $this->assertTrue($validation->passes());
    }

    public function testInvalidArgs() {
        $rules = array('item4' => array('type' => 'string', 'required' => true));
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"));
        $validation = new Validator($rules, $data);
        $i = $validation->getInvalidArgs();

        $this->assertTrue(count($i) ==  1);
        $this->assertTrue(isset($i['item4']));
    }

    public function testRequiredString() {
        $rules = array('item4' => 'required');
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item3' => array("test"));

        $validation = new Validator($rules, $data);
        $this->assertFalse($validation->passes());


        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item4' => array("test"));
        $validation = new Validator($rules, $data);
        $this->assertTrue($validation->passes());
    }

    public function testMatches() {
        $rules = array('item4' => array('matches' => 'dog|cat'));
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item4' => 'cat'
        );

        $validation = new Validator($rules, $data);
        $this->assertTrue($validation->passes());

        $rules = array('item4' => array('matches' => 'dog|CAT'));
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item4' => 'cat'
        );

        $validation = new Validator($rules, $data);
        $this->assertTrue($validation->passes());

        $rules = array('item4' => array('matches' => 'dog|cat'));
        $data = array(
            'item' => 1,
            'item2' => "a string",
            'item4' => 'fish'
        );

        $validation = new Validator($rules, $data);
        $this->assertFalse($validation->passes());
    }


    public function testTypeChecking() {
        $types = array( 'string' => 'hello', 
                        'numeric' => 3, 
                        'bool' => true, 
                        'array' => array(3, 'hello', false, array(1,2,3)), 
                        'array<string>' => array('one', 'two'),
                        'array<scalar>' => array(3, 'hello', false) );
        foreach($types as $expect => $expect_value) {
            foreach($types as $got => $got_value) {

                $msg = sprintf("Testing expect=%s vs got=%s",$expect, $got);

                $validation = new Validator(
                    array('arg' => $expect),
                    array('arg' => $got_value)
                    );

                $msg .= " " . implode(" ",$validation->getErrors());

                if (Type::isSubtypeOf($got, $expect)) {
                    $this->assertTrue($validation->passes(), $msg);
                } else {
                    if ($validation->passes()) 
                    //echo sprintf("%s is NOT a subtype of %s", $got, $expect);
                    $this->assertTrue($validation->fails(), $msg);
                }
            }
        }
    }

    public function testScalarTypeChecking() {
        $values = array("mystring", 15, 0.245, false);
        foreach($values as $value) {
            $msg = sprintf("Testing expect=%s, got value=%s","scalar", $value);
            $validation = new Validator (
                array('arg' => 'scalar'),
                array('arg' => $value)
            );      
            $this->assertTrue($validation->passes(), $msg);
        }
        
        $msg = sprintf("Testing expect=%s vs got=array","scalar", $value);
        $validation = new Validator(
                array('arg' => 'scalar'),
                array('arg' => array("one", "two"))
        );      

        $this->assertFalse($validation->passes(), $msg);
    }

    public function testTypedArrayChecked() {
        $tests = array(

            array(  "expect" => "array", 
                    "got" => array("one", "two"), 
                    "valid" => true),
            array(  "expect" => "array", 
                    "got" => array("one", 321), 
                    "valid" => true),          
            array(  "expect" => "array", 
                    "got" => array(0.235, array("something")), 
                    "valid" => true),

            array(  "expect" => "array<string>", 
                    "got" => array("one", "two"), 
                    "valid" => true),
            array(  "expect" => "array<string>", 
                    "got" => array("one", "two", 567), 
                    "valid" => false),
            array(  "expect" => "array<string>", 
                    "got" => array("one", "two", array("one")), 
                    "valid" => false),
            array(  "expect" => "array<string>", 
                    "got" => array(true, false), 
                    "valid" => false),

            array(  "expect" => "array<numeric>", 
                    "got" => array(1, 2), 
                    "valid" => true),
            array(  "expect" => "array<numeric>", 
                    "got" => array(1.34, 2), 
                    "valid" => true),
            array(  "expect" => "array<numeric>", 
                    "got" => array("one", "two", 567), 
                    "valid" => false),
            array(  "expect" => "array<numeric>", 
                    "got" => array("one", "two", array("one")), 
                    "valid" => false),
            array(  "expect" => "array<numeric>", 
                    "got" => array(true, false), 
                    "valid" => false),

        );

        foreach ($tests as $test) {
            $validation = new Validator(
                array('arg' => $test['expect']),
                array('arg' => $test['got'])
            );

            $msg = sprintf("Testing %s with value=[%s], valid=%b.  Error: %s", 
                            $test['expect'], json_encode($test['got']),
                            $test['valid'], implode(" ",$validation->getErrors()));                

            $this->assertEquals($test['valid'], $validation->passes(), $msg);
        }
    }

    public function testMixedTypes() {
        $tests = array(

            array(  "expect" => "array|bool|numeric|string", 
                    "got" => array("one"), 
                    "valid" => true
                    ),
            array(  "expect" => "array|bool|numeric|string", 
                    "got" => false, 
                    "valid" => true
                    ),
            array(  "expect" => "array|bool|numeric|string", 
                    "got" => 123, 
                    "valid" => true
                    ),
            array(  "expect" => "array|bool|numeric|string", 
                    "got" => "mystring", 
                    "valid" => true
                    ),
            array(  "expect" => "array|numeric", 
                    "got" => array("one"), 
                    "valid" => true
                    ),
            array(  "expect" => "array|numeric", 
                    "got" => 123, 
                    "valid" => true
                    ),
            array(  "expect" => "array|numeric", 
                    "got" => "mystring", 
                    "valid" => false
                    ),
            array(  "expect" => "array<string>|array<bool>", 
                    "got" => 123, 
                    "valid" => false
                    ),
            array(  "expect" => "array<string>|array<bool>", 
                    "got" => array("one","two"), 
                    "valid" => true
                    ),
            array(  "expect" => "array<string>|array<bool>", 
                    "got" => array(true,true,false), 
                    "valid" => true
                    ),
            array(  "expect" => "array<string>|array<bool>", 
                    "got" => array(4.5,7.8,91.2), 
                    "valid" => false
                    ),
        );

        foreach ($tests as $test) {
            $validation = new Validator(
                array('arg' => $test['expect']),
                array('arg' => $test['got'])
            );

            $got = (is_array($test['got'])) ? implode(",",$test['got']) 
                                            : $test['got'];

            $msg = sprintf("Testing %s with value=[%s], valid=%b.  Error: %s", 
                            $test['expect'], $got, 
                            $test['valid'], implode(" ",$validation->getErrors()));                

            $this->assertEquals($test['valid'], $validation->passes(), $msg);
        }


    }
    
}