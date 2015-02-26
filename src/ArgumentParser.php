<?php
namespace WPAS;


class ArgumentParser {

    private static $config_args = array();



    private static $field_rules = array(
        'type' => 'FieldType',
        'meta_key' => 'string',
        'taxonomy' => 'string',
        'relation' => 'string',
        'data_type' => 'string',
    );


    protected static $input_rules = array(
                'id' => 'string',
                'attributes' => 'array<scalar>',
                'field_type' => array('type' => 'FieldType', 'required' => true),
                'label' => 'string',
                'class' => 'array<string>',
                'format' => array('type' => 'InputFormat', 'required' => true),
                'placeholder' => 'string|bool',
                'values' => 'array',
                'value' => 'scalar',
                'selected' => 'array',
                'exclude' => 'array|string',
                'nested' => 'bool',
                'allow_null' => 'bool|string',
                'default_all' => 'bool',
                'default' => 'scalar',
                'pre_html' => 'string',
                'post_html' => 'string',
                'authors' => 'array<numeric>',
                'compare' => 'string',
                'operator' => 'string',
                'orderby_values' => 'array',
                'terms' => 'array<scalar>',
                'term_args' => 'array',
                'term_format' => array('type' => 'string', 'matches' => 'id|name|slug'),
    );

    private static $input_defaults = array(
                                'label' => '',
                                'placeholder' => false,
                                'values' => array(),
                                'exclude' => array(),
                                'selected' => array(),
                                'nested' => false,
                                'allow_null' => false,
                                'default_all' => false,
                                'pre_html' => '',
                                'post_html' => '',
                                'term_format' => 'slug',
                                'class' => '',
                                'nested' => false,
                                'default' => false,
                                'default_all' => false,
                                'authors' => array(),
                                'compare' => 'IN',
                                'operator' => 'AND',
    );


    public static function parse($args) {
        if (!is_array($args)) return array();

        // Parse wp_query


        // Parse config

        // Parse fields

        if (isset($args['fields']) && is_array($args['fields'])) {
            $args['fields'] = self::parseFields($args['fields']);
        }
    }

    public static function parseFields($fields_args) {
        foreach ($fields_args as $field) {


        }
    }
}
