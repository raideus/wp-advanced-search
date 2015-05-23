<?php
namespace WPAS;
require_once(dirname(__DIR__) . '/wpas.php');

class TestForm extends \PHPUnit_Framework_TestCase {

    public function testCanGetAttributes() {
        $args = array(
            'action' => 'http://google.com',
            'method' => 'GET',
            'id' => 'my_id',
            'name' => 'some_name',
            'class' => array('form-class') );

        $form = new Form("default", $args);
        $this->assertEquals($form->getAction(), 'http://google.com');
        $this->assertEquals($form->getMethod(), 'GET');
        $this->assertEquals($form->getID(), 'my_id');
        $this->assertEquals($form->getName(), 'some_name');
        $this->assertEquals($form->getClass(), array('form-class'));

    }

    public function testCanAddInput() {
        $args = array(
            'action' => 'http://google.com',
            'method' => 'GET',
            'id' => 'my_id',
            'name' => 'some_name',
            'class' => array('form-class') );

        $form = new Form("default", $args);
        $input_args = array('field_type' => 'meta_key', 'format' => 'checkbox', 'values' => array('one', 'two'));
        $input = new Input("myinput", $input_args);

        $form->addInput($input);
    }

    public function testCanGetInput() {
        $args = array(
            'action' => 'http://google.com',
            'method' => 'GET',
            'id' => 'my_id',
            'name' => 'some_name',
            'class' => array('form-class') );

        $form = new Form("default", $args);
        $input_args = array('field_type' => 'meta_key', 'format' => 'checkbox', 'values' => array('one', 'two'));
        $input = new Input("myinput", $input_args);

        $form->addInput($input);
        $inputs = $form->getInputs();
        $this->assertTrue(is_array($inputs) && count($inputs) == 1);
        $this->assertTrue($inputs[0] instanceof Input);
        $this->assertTrue($inputs[0]->getInputName() == "myinput");
    }

    public function testToHTML() {
        $args = array(
            'action' => 'http://google.com',
            'method' => 'GET',
            'id' => 'my_id',
            'name' => 'some_name',
            'class' => array('form-class') );

        $form = new Form("default", $args);

        $input_args = array('field_type' => 'meta_key', 'format' => 'checkbox', 'values' => array('one', 'two'));
        $input = new Input("myinput", $input_args);
        $form->addInput($input);

        $input_args = array('field_type' => 'search', 'format' => 'text', 'placeholder' => 'Enter keywords...');
        $input = new Input("myinput", $input_args);
        $form->addInput($input);

        $this->assertTrue(is_string($form->toHTML()));
    }


    public function testBadMethodInvokesDefault() {
        $args = array( 'method' => 'BADMETHOD' );
        $form = new Form("default", $args);
        $defaults = $form->getDefaults();
        $this->assertTrue($form->getMethod() == $defaults['method']);
    }

    public function testBadIdInvokesDefault() {
        $args = array( 'id' => 123 );
        $form = new Form("default", $args);
        $defaults = $form->getDefaults();
        $this->assertTrue($form->getID() == $defaults['id']);
    }

    public function testBadNameInvokesDefault() {
        $args = array( 'name' => 1.2 );
        $form = new Form("default", $args);
        $defaults = $form->getDefaults();
        $this->assertTrue($form->getName() == $defaults['name']);
    }

    public function testBadClassInvokesDefault() {
        $args = array( 'class' => array(1,2,3) );
        $form = new Form("default", $args);
        $defaults = $form->getDefaults();
        $this->assertTrue($form->getClass() == $defaults['class']);
    }

}