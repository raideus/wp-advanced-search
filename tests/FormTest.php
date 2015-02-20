<?php
namespace WPAS;
require_once('src/Form.php');
require_once('src/FormMethod.php');

class FormTest extends \PHPUnit_Framework_TestCase {

    public function testCanGetAttributes() {
        $rules = array();
        $args = array(  
                        'action' => 'http://google.com',
                        'method' => 'GET',
                        'id' => 'my_id',
                        'name' => 'some_name',
                        'class' => 'form-class' );

        $form = new Form($args);
        $this->assertEquals($form->getAction(), 'http://google.com');
        $this->assertEquals($form->getMethod(), 'GET');
        $this->assertEquals($form->getID(), 'my_id');
        $this->assertEquals($form->getName(), 'some_name');
        $this->assertEquals($form->getClass(), 'form-class');

    }

    /**
     * @expectedException     Exception
     */
    public function testFailsValidationWithBadAction() {
        $args = array( 'action' => 123 );
        $form = new Form($args);
    }

    /**
     * @expectedException     Exception
     */
    public function testFailsValidationWithBadMethod() {
        $args = array( 'method' => 'BADMETHOD' );
        $form = new Form($args);
    }

    /**
     * @expectedException     Exception
     */
    public function testFailsValidationWithBadID() {
        $args = array( 'id' => 123 );
        $form = new Form($args);
    }

    /**
     * @expectedException     Exception
     */
    public function testFailsValidationWithBadName() {
        $args = array( 'name' => 123 );
        $form = new Form($args);
    }

    /**
     * @expectedException     Exception
     */
    public function testFailsValidationWithBadClass() {
        $args = array( 'class' => array(1,2,3) );
        $form = new Form($args);
    }

}