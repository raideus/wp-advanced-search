<?php
namespace WPAS;
require_once(dirname(__DIR__).'/src/Field.php');

class TestField extends \PHPUnit_Framework_TestCase
{

    public function testCanBuildField()
    {
        $args = [
            'type' => 'meta_key',
            'format' => '',
            'meta_key' => 'color',
            'relation' => 'AND',
            'values' => ['red' => 'Red', 'blue' => 'Blue', 'green' => 'Green'],
        ];

        $f = new Field($args);
        $inputs = $f->getInputs();
        $this->assertTrue(count($inputs) == 1);
        $this->assertTrue(!empty($inputs['color']));
        $this->assertTrue($f->getRelation() == 'AND');

    }

    public function testCanBuildSearchField() {
        $args = ['type' => 'search',
                'label' => 'Search',
                'id' => 'hhh',
                'format' => 'text',
                'class' => 'testclass',
                'name' => 'my_search',
                'attributes' => ['data-src' => 12345, 'data-color' => 'red', 'min' => 0, 'max' => 100],
                'default' => 'something'
        ];

        $f = new Field($args);
    }

    public function testCanBuildMultiInput() {
        $args = ['type' => 'search',
            'label' => 'Search',
            'id' => 'hhh',
            'format' => 'text',
            'class' => 'testclass',
            'name' => 'my_search',
            'attributes' => ['data-src' => 12345, 'data-color' => 'red', 'min' => 0, 'max' => 100],
            'default' => 'something'
        ];

        $f = new Field($args);
    }

    public function testCanOverrideRelation() {
        $args = [
            'type' => 'taxonomy',
            'taxonomy' => 'category',
            'relation' => 'IN'
        ];
        $f = new Field($args);
        $default_relation = $f->getDefaults()['relation'];
        $this->assertTrue($f->getRelation() == $default_relation);
    }

}
