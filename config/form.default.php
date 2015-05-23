<?php
add_action("init", function() {
    $form_id = "default";

    $args = array();
    $args['wp_query'] = array('post_type' => array('post'), 'posts_per_page' => 10);

    $args['config']['meta_key_relation'] = 'AND';
    $args['config']['taxonomy_relation'] = 'AND';

    $args['fields'][] = array(
        'type' => 'search',
        'label' => 'Search'
    );

    $args['fields'][] = array(
        'type' => 'taxonomy',
        'label' => 'Category',
        'taxonomy' => 'category',
        'format' => 'select',
        'allow_null' => true,
        'operator' => 'AND'
    );

    $args['fields'][] = array(
        'type' => 'submit',
        'value' => 'Submit'
    );

    register_wpas_form($form_id, $args);
});