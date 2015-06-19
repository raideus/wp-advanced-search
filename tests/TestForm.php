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
        $attr = $form->getAttributes();
        $this->assertEquals($attr['action'], 'http://google.com');
        $this->assertEquals($attr['method'], 'GET');
        $this->assertEquals($attr['name'], 'some_name');
        $this->assertEquals($attr['class'], array('form-class'));

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
        $attr = $form->getAttributes();
        $this->assertTrue($attr['method'] == $defaults['method']);
    }


    public function testBadNameInvokesDefault() {
        $args = array( 'name' => 1.2 );
        $form = new Form("default", $args);
        $defaults = $form->getDefaults();
        $attr = $form->getAttributes();
        $this->assertTrue($attr['name'] == $defaults['name']);
    }

    public function testBadClassInvokesDefault() {
        $args = array( 'class' => array(1,2,3) );
        $form = new Form("default", $args);
        $defaults = $form->getDefaults();
        $attr = $form->getAttributes();
        $this->assertTrue($attr['class'] == $defaults['class']);
    }

}