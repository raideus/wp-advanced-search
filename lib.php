<?php
/**
 * Returns full URI of the WPAS directory
 *
 * @return string
 */
function get_wpas_uri() {
    if (defined('WPAS_URI')) {
        return rtrim(WPAS_URI,'/');
    }
    return get_stylesheet_directory_uri() . '/' . basename(__DIR__);
}

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

    $page = (isset($post['page'])) ? $post['page'] : 0;
    $request['paged'] = $page;

    $wpas_id = $request['wpas_id'];
    $wpas = new WP_Advanced_Search($wpas_id, $request);
    $q = $wpas->query();
    $template = $wpas->get_ajax()->resultsTemplate();

    $response = array();
    $response["results"] = wpas_load_template_part($template, $q);
    $response["current_page"] = $q->query_vars['paged'];
    $response["max_page"] = $q->max_num_pages;

    if ($response["results"] === false) {
        $wpas->set_error("AJAX results template '".$template."' not found in theme root.");
    }

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
function wpas_load_template_part($template, $query_object) {
    global $wp_query;

    $template_suffix = '/'.ltrim($template,'/');
    $template = get_stylesheet_directory().$template_suffix;
    if (!file_exists($template)) {
        $template = get_template_directory().$template_suffix;
        if (!file_exists($template)) return false;
    }
    $temp = $wp_query;
    $wp_query = $query_object;

    ob_start();
    load_template($template);
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

/**
 * Deregisters a search form, making it unavailable for use
 *
 * @param $name  Unique identifier for the search form
 * @return bool  True if form successfully deregistered, false if form did not exist
 */
function deregister_wpas_form($name) {
    global $WPAS_FORMS;
    if (!isset($WPAS_FORMS[$name])) return false;
    unset($WPAS_FORMS[$name]);
    return true;
}