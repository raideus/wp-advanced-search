<?php
namespace WPAS;
require_once(dirname(__DIR__) . '/wpas.php');

class TestInput extends \PHPUnit_Framework_TestCase {

    public function testCanGetAttributes() {
        $args = array(
            'class' => array('form-class'),
            'field_type' => 'search',
            'format' => 'select',
            'attributes' => array("one", "two"),
            'label' => 'mylabel',
            'placeholder' => 'myplaceholder',
            'values' => array('one', 'three', 'four'),
            'nested' => false,
            'selected' => array('one'),
            'pre_html' => '<h1>Some HTML</h1>',
            'post_html' => '<span>more code</span>',
        );

         $input = new Input('my_input', $args);
         $input->toHTML();
         $this->assertEquals($input->getInputName(), 'my_input');
         $this->assertEquals($input->getClass(), 'form-class');
         $this->assertEquals($input->getFieldType(), 'search');
         $this->assertEquals($input->getFormat(), 'select');
         $this->assertEquals($input->getAttributes(), array("one", "two"));
         $this->assertEquals($input->getLabel(), 'mylabel');
         $this->assertEquals($input->getPlaceholder(), 'myplaceholder');
         $this->assertEquals($input->getValues(), array('one', 'three', 'four'));
         $this->assertEquals($input->isNested(), false);
         $this->assertEquals($input->getSelected(), array('one'));
         $this->assertEquals($input->getPreHtml(), '<h1>Some HTML</h1>');
         $this->assertEquals($input->getPostHtml(), '<span>more code</span>');
    }

    /**
     * @expectedException     Exception
     */
    public function testFailsOnMissingFormat() {
        $args = array(
            'id' => 'my_id',
            'name' => 'some_name',
            'class' => array('form-class'),
            'field_type' => 'search',
            'attributes' => array("one", "two"),
            'label' => 'mylabel',
        );

        $input = new Input('my_input', $args);
    }

    /**
     * @expectedException     InvalidArgumentException
     */
    public function testFailsOnInvalidName() {
        $args = array(
            'id' => 'my_id',
            'name' => 'some_name',
            'class' => array('form-class'),
            'format' => 'select',
            'field_type' => 'search',
            'attributes' => array("one", "two"),
            'label' => 'mylabel',
        );

        $input = new Input(123, $args);
    }

    public function testAllowNull() {
        $args = array(
            'field_type' => 'search',
            'format' => 'select',
            'values' => array('one' => 'one', 'three' => 'three', 'four' => 'four'),
            'allow_null' => 'my_null_value'
        );
        $input = new Input('my_input', $args);
        $values = $input->getValues();
        $this->assertTrue(reset($values) == 'my_null_value');
    }


    public function testBasicSelect() {
        $args = array('field_type' => 'meta_key', 'format' => 'select', 'values' => array('one', 'two'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

    public function testBasicCheckbox() {
        $args = array('field_type' => 'meta_key', 'format' => 'checkbox', 'values' => array('one', 'two'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

    public function testBasicRadio() {
        $args = array('field_type' => 'meta_key', 'format' => 'checkbox', 'values' => array('one', 'two'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

    public function testBasicInput() {
        $args = array('field_type' => 'meta_key', 'format' => 'text', 'values' => array('somevalue'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

    public function testBasicTextarea() {
        $args = array('field_type' => 'meta_key', 'format' => 'textarea', 'values' => array('somevalue'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

    public function testBasicSubmit() {
        $args = array('field_type' => 'meta_key', 'format' => 'submit', 'values' => array('Submit'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

    public function testBasicHTML() {
        $args = array('field_type' => 'meta_key', 'format' => 'html', 'values' => array('<h1>Some HTML</h1>'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

    public function testBasicHidden() {
        $args = array('field_type' => 'meta_key', 'format' => 'hidden', 'values' => array('myvalue'));
        $input = new Input("myinput", $args);
        $input->toHTML();
    }

}