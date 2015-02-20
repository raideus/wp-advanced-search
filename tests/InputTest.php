<?php
namespace WPAS;
require_once('src/Input.php');
require_once('src/InputFormat.php');

class InputTest extends \PHPUnit_Framework_TestCase {

    public function testCanGetAttributes() {
        $rules = array();
        $args = array(  
                        'id' => 'my_id',
                        'name' => 'some_name',
                        'class' => 'form-class' );

        $input = new Input('my_input', $args);
        // $this->assertEquals($form->getAction(), 'http://google.com');
        // $this->assertEquals($form->getMethod(), 'GET');
        // $this->assertEquals($form->getID(), 'my_id');
        // $this->assertEquals($form->getName(), 'some_name');
        // $this->assertEquals($form->getClass(), 'form-class');

    }



}