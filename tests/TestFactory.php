<?php
namespace WPAS;
use WPAS\Enum\RequestVar;
use WPAS\Enum\Relation;
require_once(dirname(__DIR__) . '/wpas.php');

class TestFactory extends \PHPUnit_Framework_TestCase
{

    public function testEmptyFactory()
    {
        $args = array();
        $f = new Factory($args);
        $this->assertFalse($f->hasErrors());

    }

    public function testFactory() {
        $args = array();

        $args['fields'][] = array('type' => 'search',
            'label' => 'Search',
            'id' => 'hhh',
            'format' => 'text',
            'class' => 'testclass',
            'name' => 'my_search',
            'attributes' => array('data-src' => 12345, 'data-color' => 'red', 'min' => 0, 'max' => 100),
            'default' => 'something'
        );

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
        $this->assertFalse($f->hasErrors());
        $inputs = $f->getInputs();
        $this->assertTrue(count($inputs) == 8);

        $q = $f->buildQueryObject()->query;
    }

    public function testMetaQueryBetween() {
        $args = array();
        $prefix = RequestVar::meta_key;
        $meta_key = 'price';
        $compare = 'BETWEEN';
        $type = 'NUMERIC';

        $request = array($prefix.$meta_key.'1' => 10, $prefix.$meta_key.'2' => 25,
            $prefix.$meta_key.'3' => 30);

        $args['fields'][] = array(
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => $compare,
            'data_type' => $type,
            'inputs' => array(
                array(
                    'format' => 'text',
                ),
                array(
                    'format' => 'text'
                )
            )
        );

        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQueryObject()->query;
        $this->assertTrue(!empty($q));
        $this->assertTrue(!empty($q['meta_query']));
        $expected_query = '[{"key":"price","type":"NUMERIC","value":["10","25"],"compare":"BETWEEN"}]';
        $this->assertTrue(json_encode($q['meta_query']) == $expected_query);

    }

    public function testMetaQueryBetweenSingleInput() {
        $args = array();
        $prefix = RequestVar::meta_key;
        $meta_key = 'price';
        $compare = 'BETWEEN';
        $type = 'NUMERIC';

        $request = array($prefix.$meta_key => '0:10');

        $args['fields'][] = array(
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => $compare,
            'data_type' => $type,
            'inputs' => array(
                array(
                    'format' => 'select',
                    'values' => array(
                        '0:10' => '0 to 10',
                        '11:25' => '11 to 25',
                        '26:' => '26+'
                    )
                ),

                )
        );

        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQueryObject()->query;
        $this->assertTrue(!empty($q));
        $this->assertTrue(!empty($q['meta_query']));

        $expected_query = '[{"key":"price","type":"NUMERIC","value":["0","10"],"compare":"BETWEEN"}]';
        $this->assertTrue(json_encode($q['meta_query']) == $expected_query);

        $request = array($prefix.$meta_key => '26:');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQueryObject()->query;
        $this->assertTrue(!empty($q));
        $this->assertTrue(!empty($q['meta_query']));
        $expected_query = '[{"key":"price","type":"NUMERIC","value":"26","compare":">="}]';
        $this->assertTrue(json_encode($q['meta_query']) == $expected_query);

    }

    public function testMetaQueryWithOneKey()
    {
        $args = array();
        $meta_key = 'color';
        $args['fields'][] = array(
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => 'IN',
            'relation' => 'AND',
            'data_type' => 'CHAR',
            'format' => 'checkbox'
        );

        $request = array('meta_'.$meta_key => array('red','blue'));
        $f = new Factory($args, $request);
        $q = $f->buildQueryObject()->query;

        $this->assertFalse(empty($q['meta_query']));
        $this->assertTrue(count($q['meta_query']) == 1);

    }

    public function testMetaQueryWithMultiKey()
    {
        $args = array();
        $meta_key = 'color';
        $meta_key2 = 'shape';

        $args['fields'][] = array(
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => 'LIKE',
            'relation' => 'OR',
            'data_type' => 'ARRAY<CHAR>',
            'format' => 'checkbox'
        );
        $args['fields'][] = array(
            'type' => 'meta_key',
            'meta_key' => $meta_key2,
            'compare' => 'LIKE',
            'relation' => 'OR',
            'data_type' => 'ARRAY<CHAR>',
            'format' => 'checkbox'
        );

        $request = array('meta_'.$meta_key => array('red','blue'), 'meta_'.$meta_key2 => array('square','circle'));
        $f = new Factory($args, $request);
        $q = $f->buildQueryObject()->query;

        $this->assertFalse(empty($q['meta_query']));
        $this->assertTrue(count($q['meta_query']) == 3);
        $this->assertTrue(count($q['meta_query'][0]) == 3);
        $this->assertTrue(count($q['meta_query'][1]) == 3);

    }

    public function testBadMetaKeyRelation()
    {
        $args = array();
        $meta_key = 'color';
        $meta_key2 = 'shape';

        $args['meta_key_relation'] = "BAD";

        $args['fields'][] = array(
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => 'LIKE',
            'relation' => 'OR',
            'data_type' => 'ARRAY<CHAR>',
            'format' => 'checkbox'
        );
        $args['fields'][] = array(
            'type' => 'meta_key',
            'meta_key' => $meta_key2,
            'compare' => 'LIKE',
            'relation' => 'OR',
            'data_type' => 'ARRAY<CHAR>',
            'format' => 'checkbox'
        );

        $request = array('meta_'.$meta_key => array('red','blue'), 'meta_'.$meta_key2 => array('square','circle'));
        $f = new Factory($args, $request);
        $q = $f->buildQueryObject()->query;

        $this->assertTrue($q['meta_query']['relation'] == Relation::_default);

    }


    public function testTaxQuery()
    {
        $args = array();
        $tax = 'category';
        $args['fields'][] = array(
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'operator' => 'IN',
            'format' => 'checkbox'
        );

        $expected_query = '[{"taxonomy":"category","operator":"IN","field":"slug","terms":["red","blue"]}]';

        $request = array(RequestVar::taxonomy . $tax => array('red','blue'));
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQueryObject()->query;
        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);

    }

    public function testTaxQueryMultiTax() {
        $args = array();
        $tax = 'category';
        $tax2 = 'post_tag';
        $args['fields'][] = array(
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'operator' => 'AND',
            'format' => 'checkbox'
        );
        $args['fields'][] = array(
            'type' => 'taxonomy',
            'taxonomy' => $tax2,
            'operator' => 'IN',
            'format' => 'checkbox'
        );

        $expected_query = '{"0":{"taxonomy":"category","operator":"AND","field":"slug","terms":["red","blue"]},"1":{"taxonomy":"post_tag","operator":"IN","field":"slug","terms":["square","circle"]},"relation":"AND"}';

        $request = array(RequestVar::taxonomy . $tax => array('red','blue'),
            RequestVar::taxonomy . $tax2 => array('square','circle'));
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQueryObject()->query;
        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);

    }

    public function testTaxQueryMultiInput() {
        $args = array();
        $tax = 'category';
        $args['fields'][] = array(
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'relation' => 'AND',
            'inputs' => array(
                array(
                    'format' => 'select',
                    'operator' => 'IN'
                ),
                array(
                    'format' => 'select',
                    'operator' => 'NOT IN'
                )
            )

        );

        $expected_query = '[{"0":{"taxonomy":"category","operator":"IN","field":"slug","terms":"red"},"1":{"taxonomy":"category","operator":"NOT IN","field":"slug","terms":["blue","green"]},"relation":"AND"}]';

        $request = array(RequestVar::taxonomy . $tax . '1' => 'red',
            RequestVar::taxonomy . $tax . '2' => array('blue','green'));
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQueryObject()->query;

        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);
    }

    public function testTaxQueryMultiInputEmptyInput() {
        $args = array();
        $tax = 'category';
        $args['fields'][] = array(
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'relation' => 'AND',
            'inputs' => array(
                array(
                    'format' => 'select',
                    'operator' => 'IN'
                ),
                array(
                    'format' => 'select',
                    'operator' => 'NOT IN'
                )
            )
        );

        $expected_query = '[{"taxonomy":"category","operator":"IN","field":"slug","terms":"red"}]';

        $request = array(RequestVar::taxonomy . $tax . '1' => 'red',
            RequestVar::taxonomy . $tax . '2' => array());
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQueryObject()->query;
        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);
    }

    public function testFactoryComplexRequest() {
        $args = array();

        $args['fields'][] = array('type' => 'search',
            'label' => 'Search',
            //'id' => 'hhh',
            'format' => 'text',
            'class' => 'testclass',
            'name' => 'my_search',
            'attributes' => array('data-src' => 12345, 'data-color' => 'red', 'min' => 0, 'max' => 100),
            'default' => 'something'
        );

        $args['fields'][] = array('type' => 'post_type',
            'label' => 'Post Type',
            'values' => array('post' => 'Post', 'page' => 'Page', 'event' => 'Event'),
            'format' => 'checkbox',
            'default_all' => true);

        $args['fields'][] = array('type' => 'order',
            'label' => 'Order',
            'id' => 'checkoutmyid',
            'values' => array('' => '', 'ASC' => 'ASC', 'DESC' => 'DESC'),
            'format' => 'select');

        $args['fields'][] = array('type' => 'orderby',
            'label' => 'Order By',
            'id' => 'myorder',
            'allow_null' => false,
            'format' => 'radio');

        $args['fields'][] = array('type' => 'posts_per_page',
            'label' => 'Order By',
            'id' => 'myorder',
            'allow_null' => false,
            'format' => 'radio');

        $args['fields'][] = array('type' => 'submit',
            'value' => 'Search',
            'attributes' => array('one' => 'five'),
        );

        $request = array('order'=>'ASC', 'ptype'=>array('post','page'), 'search_query' => 'testing', 'posts_per_page' => 12);
        $expected_query = '{"post_type":["post","page"],"order":"ASC","posts_per_page":"12","s":"testing","paged":1}';
        $e =              '{"post_type":["post","page"],"order":"ASC","posts_per_page":12,"s":"testing","paged":1}';

        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQueryObject()->query;
        $this->assertTrue(json_encode($q) == $expected_query);

    }

    public function testFactoryDateQuery() {
        $args = array();
        $args['fields'][] = array(
            'type' => 'date',
            'format' => 'select',
            'date_type' => 'month'
        );

        $expected_query = '{"year":"2014","month":"01"}';

        $request = array(RequestVar::date . 'm'  => '2014-01');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQueryObject()->query;
        $this->assertFalse(empty($q['date_query']));
        $this->assertTrue(json_encode($q['date_query']) == $expected_query);

    }

    public function testOrderbyValues() {
        $args = array();
        $args['fields'][] = array(
            'type' => 'orderby',
            'format' => 'radio',
            'default' => 'event_date',
            'title' => 'Order by',
            'orderby_values' =>
                array(
                    'event_date' => array('label' => 'Date',
                        'meta_key' => true,
                        'orderby' => 'meta_value'),
                    'event_price' => array('label' => 'Date',
                        'meta_key' => true,
                        'orderby' => 'meta_value_num'),
                    'title' => array('label' => 'Title'),
                ));

        // Test meta_value_num
        $request = array(RequestVar::orderby  => 'event_price');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQueryObject()->query;
        $this->assertTrue($q['orderby'] == 'meta_value_num');
        $this->assertTrue($q['meta_key'] == 'event_price');

        // Test meta_value
        $request = array(RequestVar::orderby  => 'event_date');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQueryObject()->query;
        $this->assertTrue($q['orderby'] == 'meta_value');
        $this->assertTrue($q['meta_key'] == 'event_date');

        // Test regular orderby
        $request = array(RequestVar::orderby  => 'title');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQueryObject()->query;
        $this->assertTrue($q['orderby'] == 'title');
        $this->assertTrue(!isset($q['meta_key']));

    }

    public function testGenericFieldIdApplies() {
        $args = array();
        $args['fields'][] = array(
          'type' => 'generic',
            'format' => 'select',
            'id' => 'bleep'
        );
        $f = new Factory($args);
        $inputs = $f->getForm()->getInputs();
        $input = $inputs[0];
        $this->assertTrue($input->getId() == 'bleep');
    }



}