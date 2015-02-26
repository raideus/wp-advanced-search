<?php
namespace WPAS;
require_once(dirname(__DIR__).'/src/Factory.php');

class TestFactory extends \PHPUnit_Framework_TestCase
{

    public function testEmptyFactory()
    {
        $args = array();
        //$f = new Factory($args);
       // $this->assertFalse($f->hasErrors());

    }

    public function testFactory() {
        $args = array();

        $args['fields'][] = ['type' => 'search',
            'label' => 'Search',
            'id' => 'hhh',
            'format' => 'text',
            'class' => 'testclass',
            'name' => 'my_search',
            'attributes' => ['data-src' => 12345, 'data-color' => 'red', 'min' => 0, 'max' => 100],
            'default' => 'something'
        ];

        $args['fields'][] = array('type' => 'meta_key',
            'label' => 'Color',
            'format' => 'checkbox',
            'meta_key' => 'color',
            'compare' => 'LIKE',
            'data_type' => 'CHAR',
            'relation' => 'OR',
            'values' => array(
                'red' => 'Red',
                'blue' => 'Blue',
                'green' => 'Green',
                'orange' => 'Orange',
                'purple' => 'Purple',
                'yellow' => 'Yellow'
            ));

        $args['fields'][] = array('type' => 'meta_key',
            'label' => 'Animal',
            'format' => 'checkbox',
            'meta_key' => 'custom1',
            'compare' => 'IN',
            'data_type' => 'CHAR',
            'relation' => 'OR',
            'values' => array(
                'Dog' => 'Dog',
                'Cat' => 'Cat',
                'Giraffe' => 'Giraffe',
            ));

        $args['fields'][] = array('type' => 'post_type',
            'label' => 'Post Type',
            'values' => array('post' => 'Post', 'page' => 'Page', 'event' => 'Event'),
            'format' => 'checkbox',
            'default_all' => true);

        $args['fields'][] = array('type' => 'post_type',
            'label' => 'Post Type',
            'values' => array('post' => 'Post', 'page' => 'Page', 'event' => 'Event'),
            'format' => 'checkbox',
            'default_all' => true);

        $args['fields'][] = array('type' => 'order',
            'label' => 'Order',
            'class' => array('orderfield', 'zzz'),
            'id' => 'checkoutmyid',
            'values' => array('' => '', 'ASC' => 'ASC', 'DESC' => 'DESC'),
            'format' => 'select');

        $args['fields'][] = array('type' => 'orderby',
            'label' => 'Order By',
            //'exclude' => 'post_modified',
            'id' => 'myorder',
            'class' => 'orderclass anotherone',
            'allow_null' => false,
            'format' => 'radio');

        $args['fields'][] = array('type' => 'submit',
            'value' => 'Search',
            'class' => 'submitclasss',
            'attributes' => array('one' => 'five'),
        );

        $f = new Factory($args);
        $inputs = $f->getInputs();
        $this->assertFalse($f->hasErrors());

    }
}