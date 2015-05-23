<?php
/*
Template Name: WPAS Ajax Demo

A custom page template to demonstrate the functionality of WP Advanced Search.
To use, simply create a new page and select "Advanced Search Demo" under
Page Attributes > Template.
*/
define('WPAS_DEBUG', true);
define('WPAS_DEBUG_LEVEL', 'log');
//define('WPAS_DISABLE_WRAPPERS', false);
get_header();


$args = array();
$args['wp_query'] = array('post_type' => array('post','page','event'), 'posts_per_page' => 2);
$args['config']['meta_key_relation'] = 'AND';
$args['config']['taxonomy_relation'] = 'AND';

// $args['config']['form'] = array(
//         'action' => 'http://mysite.domain/search-results-page',
//         'method' => 'GET',
//         'id' => 'my-event-search',
//         'name' => 'my-event-search-name',
//         'class' => 'some-unique-class-name',
// );

$args['fields'][] = ['type' => 'search',
'label' => 'Search',
'id' => 'hhh',
'format' => 'text',
'class' => 'testclass',
'name' => 'my_search',
'attributes' => ['data-src' => 12345, 'data-color' => 'red', 'min' => 0, 'max' => 100],
'value' => ''];

// $args['fields'][] = array('type' => 'meta_key',
//                                 'label' => 'Meta Key',
//                                 'format' => 'text',
//                                                      'pre_html' => '<h4>Sup dawg</h4>',
//                                                      'post_html' => '<span><strong>just chillin</strong></span>',
//                                 'meta_key' => 'minimum_price',
//                                 'id' => 'minprice',
//                                 'attributes' => array('data-src' => 'google.com'),
//                                 'class' => 'mymetafield',
//                                 'value' => '');

$args['fields'][] = array('type' => 'meta_key',
'label' => 'Color',
'format' => 'checkbox',
'meta_key' => 'color',
'compare' => 'LIKE',
'data_type' => 'ARRAY<CHAR>',
    'relation' => 'AND',
    'values' => array(
    'red' => 'Red',
    'blue' => 'Blue',
    'green' => 'Green',
    'orange' => 'Orange',
    'purple' => 'Purple',
    'yellow' => 'Yellow'
    ));



    $args['fields'][] = ['type' => 'taxonomy',
    'relation' => 'OR',
    'taxonomy' => 'category',

        'inputs' => [

            [
            'label' => 'Category1',
            'exclude' => array('photos'),

            'format' => 'select',
            'allow_null' => true,
            'nested' => true,
            'operator' => 'IN'
            ],

            [
            'label' => 'Category2',
            'exclude' => array('photos'),

            'format' => 'select',
            'allow_null' => true,
            'nested' => true,
            'operator' => 'IN'
            ],

        ],

    ];

    $args['fields'][] = array('type' => 'taxonomy',
    'label' => 'Tag',
    'taxonomy' => 'post_tag',
    'format' => 'select',
    'allow_null' => true,
    'operator' => 'AND');



    $args['fields'][] = [
    'type' => 'meta_key',
    'meta_key' => 'price',
    'label' => 'Price',
    'compare' => 'BETWEEN',
    'data_type' => 'NUMERIC',
    'inputs' => [
        [
        'format' => 'select',
        'allow_null' => true,
        'values' => [
        ':10' => '10 and under',
        '11:25' => '11 to 25',
        '26:' => '26 and up']
        ],

        ]
    ];


    $args['fields'][] = array('type' => 'submit',
    'value' => 'Go',
    'class' => 'submitclasss',
    'attributes' => array('one' => 'five'),
    );

register_wpas_form("sample", $args);
