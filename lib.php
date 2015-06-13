<?php

/**
 * Constructs JSON-formatted response object for AJAX requests
 *
 * @param array $post
 * @return mixed|string|void
 */
function wpas_build_ajax_response(array $post) {
    $request = array();

    if (isset($post['form_data'])) {
        parse_str($post['form_data'], $request);
    }

    $wpas_id = $request['wpas_id'];
    $wpas = new WP_Advanced_Search($wpas_id, $request);
    $q = $wpas->query();
    $template = $wpas->get_ajax()->resultsTemplate();

    $response = array();
    $response["results"] = load_template_part($template,$q);
    //$response["pagination"] = $pagination;
    $response["current_page"] = $q->query_vars['paged'];
    $response["max_page"] = $q->max_num_pages;

    $response["debug"] = "";
    if ($wpas->debug_enabled()) $response["debug"] = "<pre>". $wpas->create_debug_output() . "</pre>";

    return json_encode($response);
}

/**
 * Loads and returns a template part
 *
 * Used with AJAX requests to build the list of search results
 *
 * @param $template
 * @param $query_object
 * @return string
 */
function load_template_part($template, $query_object) {
    global $wp_query;

    $template = ltrim($template,'/');
    $temp = $wp_query;
    $wp_query = $query_object;

    ob_start();
    load_template(dirname(__DIR__).'/'.$template);
    $var = ob_get_contents();
    ob_end_clean();

    $wp_query = $temp;
    return $var;
}

/**
 * Registers a search form, making it available for use in templates
 *
 * @param $name  Unique identifier for the search form
 * @param $args  The form's configuration arguments
 */
function register_wpas_form($name, $args) {
    global $WPAS_FORMS;
    if (!is_array($args)) return;
    $args["wpas_id"] = $name;
    $WPAS_FORMS[$name] = $args;
}