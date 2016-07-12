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
    $response['query'] = $q;
    $response["count"] = $q->found_posts; //Access the query object on order to retrieve the total number of found posts.
    $response["results"] = wpas_load_template_part($template, $q);
    $response["current_page"] = $q->query_vars['paged'];
    $response["max_page"] = $q->max_num_pages;
    $response["values"] = wpas_get_tax_count($wpas->inputs, $q, $wpas->get_args());
    $response["selected"] = wpas_get_selected($wpas->inputs);
    
    if ($response["results"] === false) {
        $wpas->set_error("AJAX results template '".$template."' not found in theme root.");
    }
    
    $response["debug"] = "";
    if ($wpas->debug_enabled()) $response["debug"] = "<pre>". $wpas->create_debug_output() . "</pre>";

    return json_encode($response);
}

function wpas_get_selected($inputs){
    $response = array();
    foreach ($inputs as $input){
        $selected = $input->getSelected();

        //Apply some filters
            if(empty($selected)) continue;
            if($input->getFieldType() != 'taxonomy' && $input->getFieldType() != 'search' && $input->getFieldType() != 'meta_key') continue; 
            if($selected[0] == '') continue; //If text inputs are empty, continue. Otherwise, they will appear on filters even if they are empty.
        //

        if(isset($input->taxonomy)){
            $label = get_taxonomy($input->taxonomy);
            $terms = get_terms(array('taxonomy' => $input->taxonomy),array('slug' => $selected));
        }
        $response[] = array(
            'selected' => isset($terms) ? $terms : $selected, 
            'type' => $input->getFieldType(),  
            'id' => $input->getId(),
            'label' => isset($label) ? $label->label : $input->getLabel(), 
        );
    }

    return $response;
}

function wpas_get_tax_count($inputs, $q, $query_args){

    $response = array();
    $values = array();
    $all_values = array();
    $tax = array();
    
    foreach($inputs as $id => $input){
        if(isset($input->taxonomy)){ 
            $values[$input->taxonomy] = array(
                'selected' => $input->getSelected(), 
                'format' => $input->term_format, 
                'operator' => $input->operator, 
            );
            $all_values[$input->getId()] = $input->getValues();
            $tax[] = array('tax' => $input->taxonomy, 'format' => $input->term_format);

            $response[$input->getId()]['hide'] = $input->hideEmpty(); //Set hide_empty option so AJAX knows whether to hide it.
        }
    }
   
    $args = array();
    
    $args['meta_query'] = $q->meta_query->queries; //If a meta_query is set, set it to each subQuery.
    $i = 0;
    foreach($values as $taxonomy => $elements){
        $terms = array();

        $args['tax_query'][$i] = array(
                'taxonomy' => $taxonomy,
                'operator' => $elements['operator'],
                'field' => $elements['format'],  
            );
        foreach($elements['selected'] as $selection){
            $terms[] = $selection;
        }

        if(!empty($terms)){
            $args['tax_query'][$i]['terms'] = $terms;
        }
        
        if(!isset($args['tax_query'][$i]['terms']))  unset($args['tax_query'][$i]);

        $i++;
    }

    //If tax_query was set on the form, include it here.
    if(!empty($query_args['wp_query']['tax_query'])) $args['tax_query'][] = $query_args['wp_query']['tax_query'];

    $i = 0;   
    foreach($all_values as $id =>  $value){
        foreach($value as $slug => $label){
            if(isset($args['tax_query'][$i])){
                array_push($args['tax_query'][$i]['terms'], $slug);
                $query = new WP_Query($args);
                $response[$id][$slug] = $query->found_posts;
                array_pop($args['tax_query'][$i]['terms']);
            }
            else{
                $args['tax_query'][] = array(
                    'taxonomy' => $tax[$i]['tax'],
                    'field' => $tax[$i]['format'],
                    'terms' => $slug,
                );
                $query = new WP_Query($args);
                $response[$id][$slug] = $query->found_posts;
                array_pop($args['tax_query']);
            }
        }
        $i++;
    }

    return $response;

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