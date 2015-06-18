<?php
namespace WPAS;
use WPAS\Enum\RequestMethod;
require_once(dirname(__DIR__) . '/wpas.php');

class TestMetaQuery extends \PHPUnit_Framework_TestCase {

    public function testSingleValueArrayInput() {
        $args = array('type' => 'meta_key',
            'label' => 'Color',
            'format' => 'multi-select',
            'meta_key' => 'color',
            'compare' => 'LIKE',
            'data_type' => 'ARRAY<CHAR>',
            'relation' => 'AND',
            'default' => 'red',
            'values' => array(
                'red' => 'Red',
                'blue' => 'Blue',
                'green' => 'Green',
                'orange' => 'Orange',
                'purple' => 'Purple',
                'yellow' => 'Yellow'
            ));
        $fields = array();
        $fields[] = new Field($args);

        $request = new HttpRequest( array('meta_color' => array('red')) );
        $meta_query_obj = new MetaQuery($fields, 'AND', $request);
        $meta_query = $meta_query_obj->getQuery();
        $this->assertFalse(empty($meta_query));
        $this->assertTrue(count($meta_query) == 1);
        $this->assertFalse(empty($meta_query[0]));
        $this->assertTrue($meta_query[0]['value'] == 'red');
        $this->assertTrue($meta_query[0]['compare'] == 'LIKE');
        $this->assertTrue($meta_query[0]['type'] == 'CHAR');
        $this->assertTrue($meta_query[0]['key'] == 'color');
    }

}