<?php
namespace WPAS;
require_once(dirname(__DIR__).'/wp-advanced-search.php');

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

    public function testCanBuildMultiInput() {
        $meta_key = 'price';
        $args = [
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => 'BETWEEN',
            'data_type' => 'NUMERIC',
            'group_method' => 'merge',
            'inputs' => [
                [
                    'format' => 'text',
                ],
                [
                    'format' => 'text'
                ]
            ]
        ];

        $f = new Field($args);
        $inputs = $f->getInputs();
        $this->assertTrue(count($inputs) == 2);
        $this->assertTrue($f->getCompare() == 'BETWEEN');
        $this->assertTrue($f->getFieldId() == $meta_key);
    }

    public function testCanOverrideInvalidRelation() {
        $args = [
            'type' => 'taxonomy',
            'taxonomy' => 'category',
            'relation' => 'IN'
        ];
        $f = new Field($args);
        $default_relation = $f->getDefaults()['relation'];
        $this->assertTrue($f->getRelation() == $default_relation);
    }

    /**
     * @expectedException     WPAS\ValidationException
     */
    public function testThrowsExceptionOnMissingType() {
        $args = [
            'taxonomy' => 'category',
            'relation' => 'IN'
        ];
        $f = new Field($args);
    }

}
