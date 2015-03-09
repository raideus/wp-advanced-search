<?php
namespace WPAS;
use WPAS\Enum\RequestVar;
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
        $this->assertFalse($f->hasErrors());
        $inputs = $f->getInputs();
        $this->assertTrue(count($inputs) == 8);
        $q = $f->buildQuery();
    }

    public function testCanGetRequest() {
        $args = array();
        $request = array('search_query' => 'hello',
                         'color' => array('blue' => 'Blue',
                                        'red' => 'Red'));
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $r = $f->getRequest();
        $this->assertTrue(!empty($r));
        $this->assertTrue($r['search_query'] == 'hello');
    }

    public function testMetaQueryBetween() {
        $args = [];
        $prefix = RequestVar::meta_key;
        $meta_key = 'price';
        $compare = 'BETWEEN';
        $type = 'NUMERIC';

        $request = array($prefix.$meta_key.'1' => 10, $prefix.$meta_key.'2' => 25,
            $prefix.$meta_key.'3' => 30);

        $args['fields'][] = [
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => $compare,
            'data_type' => $type,
            'inputs' => [
                [
                    'format' => 'text',
                ],
                [
                    'format' => 'text'
                ]
            ]
        ];

        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQuery();
        $this->assertTrue(!empty($q));
        $this->assertTrue(!empty($q['meta_query']));
        $expected_query = '[{"key":"price","type":"NUMERIC","value":["10","25"],"compare":"BETWEEN"}]';
        $this->assertTrue(json_encode($q['meta_query']) == $expected_query);

    }

    public function testMetaQueryBetweenSingleInput() {
        $args = [];
        $prefix = RequestVar::meta_key;
        $meta_key = 'price';
        $compare = 'BETWEEN';
        $type = 'NUMERIC';

        $request = array($prefix.$meta_key => '0-10');

        $args['fields'][] = [
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => $compare,
            'data_type' => $type,
            'inputs' => [
                [
                    'format' => 'select',
                    'values' => [
                        '0-10' => '0 to 10',
                        '11-25' => '11 to 25',
                        '26+' => '26+'
                    ]
                ],

            ]
        ];

        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQuery();
        $this->assertTrue(!empty($q));
        $this->assertTrue(!empty($q['meta_query']));

        $expected_query = '[{"key":"price","type":"NUMERIC","value":["0","10"],"compare":"BETWEEN"}]';
        $this->assertTrue(json_encode($q['meta_query']) == $expected_query);

        $request = array($prefix.$meta_key => '26+');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQuery();
        $this->assertTrue(!empty($q));
        $this->assertTrue(!empty($q['meta_query']));
        $expected_query = '[{"key":"price","type":"NUMERIC","value":"26","compare":">="}]';
        $this->assertTrue(json_encode($q['meta_query']) == $expected_query);

    }

    public function testMetaQueryWithOneKey()
    {
        $args = [];
        $meta_key = 'color';
        $args['fields'][] = [
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => 'LIKE',
            'relation' => 'AND',
            'data_type' => 'CHAR',
            'format' => 'checkbox'
        ];

        $request = array($meta_key => ['red','blue']);
        $f = new Factory($args, $request);
        $q = $f->buildQuery();

        //print_r($q['meta_query']);

    }

    public function testMetaQueryWithMultiKey()
    {
        $args = [];
        $meta_key = 'color';
        $meta_key2 = 'shape';
        $args['fields'][] = [
            'type' => 'meta_key',
            'meta_key' => $meta_key,
            'compare' => 'LIKE',
            'relation' => 'OR',
            'data_type' => 'CHAR',
            'format' => 'checkbox'
        ];
        $args['fields'][] = [
            'type' => 'meta_key',
            'meta_key' => $meta_key2,
            'compare' => 'LIKE',
            'relation' => 'OR',
            'data_type' => 'ARRAY<CHAR>',
            'format' => 'checkbox'
        ];

        $request = array($meta_key => ['red','blue'], $meta_key2 => ['square','circle']);
        $f = new Factory($args, $request);
        $q = $f->buildQuery();

        //print_r($q['meta_query']);
    }


    public function testTaxQuery()
    {
        $args = [];
        $tax = 'category';
        $args['fields'][] = [
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'operator' => 'IN',
            'format' => 'checkbox'
        ];

        $expected_query = '[{"taxonomy":"category","operator":"IN","field":"slug","terms":["red","blue"]}]';

        $request = array(RequestVar::taxonomy . $tax => ['red','blue']);
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQuery();
        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);

    }

    public function testTaxQueryMultiTax() {
        $args = [];
        $tax = 'category';
        $tax2 = 'post_tag';
        $args['fields'][] = [
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'operator' => 'AND',
            'format' => 'checkbox'
        ];
        $args['fields'][] = [
            'type' => 'taxonomy',
            'taxonomy' => $tax2,
            'operator' => 'IN',
            'format' => 'checkbox'
        ];

        $expected_query = '{"0":{"taxonomy":"category","operator":"AND","field":"slug","terms":["red","blue"]},"1":{"taxonomy":"post_tag","operator":"IN","field":"slug","terms":["square","circle"]},"relation":"AND"}';

        $request = array(RequestVar::taxonomy . $tax => ['red','blue'],
            RequestVar::taxonomy . $tax2 => ['square','circle']);
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQuery();
        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);

    }

    public function testTaxQueryMultiInput() {
        $args = [];
        $tax = 'category';
        $args['fields'][] = [
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'relation' => 'AND',
            'inputs' => [
                [
                    'format' => 'select',
                    'operator' => 'IN'
                ],
                [
                    'format' => 'select',
                    'operator' => 'NOT IN'
                ]
            ]


        ];

        $expected_query = '[{"0":{"taxonomy":"category","operator":"IN","field":"slug","terms":"red"},"1":{"taxonomy":"category","operator":"NOT IN","field":"slug","terms":["blue","green"]},"relation":"AND"}]';

        $request = array(RequestVar::taxonomy . $tax . '1' => 'red',
            RequestVar::taxonomy . $tax . '2' => array('blue','green'));
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQuery();

        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);
    }

    public function testTaxQueryMultiInputEmptyInput() {
        $args = [];
        $tax = 'category';
        $args['fields'][] = [
            'type' => 'taxonomy',
            'taxonomy' => $tax,
            'relation' => 'AND',
            'inputs' => [
                [
                    'format' => 'select',
                    'operator' => 'IN'
                ],
                [
                    'format' => 'select',
                    'operator' => 'NOT IN'
                ]
            ]
        ];

        $expected_query = '[{"taxonomy":"category","operator":"IN","field":"slug","terms":"red"}]';

        $request = array(RequestVar::taxonomy . $tax . '1' => 'red',
            RequestVar::taxonomy . $tax . '2' => array());
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQuery();
        $this->assertTrue(json_encode($q['tax_query']) == $expected_query);
    }

    public function testFactoryComplexRequest() {
        $args = array();

        $args['fields'][] = ['type' => 'search',
            'label' => 'Search',
            //'id' => 'hhh',
            'format' => 'text',
            'class' => 'testclass',
            'name' => 'my_search',
            'attributes' => ['data-src' => 12345, 'data-color' => 'red', 'min' => 0, 'max' => 100],
            'default' => 'something'
        ];

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

        $request = ['order'=>'ASC', 'ptype'=>['post','page'], 'search_query' => 'testing', 'posts_per_page' => 12];
        $expected_query = '{"post_type":["post","page"],"order":"ASC","posts_per_page":"12","s":"testing","paged":1}';

        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQuery();
        $this->assertTrue(json_encode($q) == $expected_query);

    }

    public function testFactoryDateQuery() {
        $args = [];
        $args['fields'][] = [
            'type' => 'date',
            'format' => 'select',
            'date_type' => 'month'
        ];

        $expected_query = '{"year":"2014","month":"01"}';

        $request = array(RequestVar::date . 'm'  => '2014-01');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());

        $q = $f->buildQuery();

        $this->assertTrue(json_encode($q['date_query']) == $expected_query);

    }

    public function testOrderbyValues() {
        $args = [];
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
        $q = $f->buildQuery();
        $this->assertTrue($q['orderby'] == 'meta_value_num');
        $this->assertTrue($q['meta_key'] == 'event_price');

        // Test meta_value
        $request = array(RequestVar::orderby  => 'event_date');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQuery();
        $this->assertTrue($q['orderby'] == 'meta_value');
        $this->assertTrue($q['meta_key'] == 'event_date');

        // Test regular orderby
        $request = array(RequestVar::orderby  => 'title');
        $f = new Factory($args, $request);
        $this->assertFalse($f->hasErrors());
        $q = $f->buildQuery();
        $this->assertTrue($q['orderby'] == 'title');
        $this->assertTrue(!isset($q['meta_key']));

    }


}