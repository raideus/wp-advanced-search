<?php
/*
Plugin Name: WP Advanced Search Framework
Plugin URI: http://wpadvancedsearch.com
Description: 
Version: 1.4
Author: Sean Butze
Author URI: http://seanbutze.com
License: GPLv2 or later
*/

define('WPAS_AJAX', true);
require_once('config/form.default.php');

$WPAS_FORMS = array();


/**
 * Class Autoloader
 *
 * Adapted from PHP-FIG:
 * http://www.php-fig.org/psr/psr-4/examples/
 *
 */
spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'WPAS\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Ajax Stuff

function wpas_scripts() {
        wp_enqueue_script( 'ajax-scripts', plugins_url( 'js/ajax.js', __FILE__ ), array(), '1', false );
        wp_enqueue_script( 'admin-ajax', admin_url( 'admin-ajax.php' ), array(), '1', false );
        wp_localize_script( 'admin-ajax', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
add_action('wp_enqueue_scripts', 'wpas_scripts');

function load_template_part($query_object) {
    global $wp_query;
    $temp = $wp_query;
    $wp_query = $query_object;
    ob_start();
    load_template(__DIR__.'/template-sample.php');
    $var = ob_get_contents();
    ob_end_clean();

    //return "<pre>". print_r($q,true) ."</pre>";

    $wp_query = $temp;
    //return $query_object;
    return $var;
}

function wpas_ajax_load() {
    global $post;

    $request = array();

    if (isset($_POST['form_data'])) {
        parse_str($_POST['form_data'], $request);
    }

    $wpas_id = $request['wpas_id'];
    $wpas = new WP_Advanced_Search($wpas_id, $request);
    $q = $wpas->query();
    $pagination = $wpas->get_pagination($q);


    $response = array();
    $response["results"] = load_template_part($q);
    $response["pagination"] = $pagination;

    $log_level = (defined('WPAS_DEBUG_LEVEL')) ? WPAS_DEBUG_LEVEL : 'log';
    $response["debug"] = "<pre>".$wpas->create_debug_output($log_level)."</pre>";

    echo json_encode($response);
    wp_die();
}
// creating Ajax call for WordPress
add_action( 'wp_ajax_nopriv_wpas_ajax_load', 'wpas_ajax_load' );
add_action( 'wp_ajax_wpas_ajax_load', 'wpas_ajax_load' );

//


function register_wpas_form($name, $args) {
    global $WPAS_FORMS;
    $args["wpas_id"] = $name;
    $WPAS_FORMS[$name] = $args;
}


class WP_Advanced_Search {
    private $factory;
    private $errors;
    private $args;
    private $request;

    function __construct($id = '', $request = false) {
        $this->errors = array();
        $this->args = $this->get_form_args($id);
        $this->request = ($request) ? $request : $_REQUEST;
        $this->factory = new WPAS\Factory($this->args, $this->request);
    }

    public function get_form_args($name) {
        global $WPAS_FORMS;

        if (empty($WPAS_FORMS)) {
            $this->errors[] = "No forms have been configured.";
            return array();
        }
        if (empty($name)) {
            if (!empty($WPAS_FORMS['default'])) return $WPAS_FORMS['default'];
            else return reset($WPAS_FORMS);
        } else if (empty($WPAS_FORMS[$name])) {
            $this->errors[] = "WPAS form with ID \"".$name."\" is not registered.";
            return array();
        }
        return $WPAS_FORMS[$name];
    }

    /**
     * Print HTML content of the search form
     */
    public function the_form() {
        echo $this->factory->getForm()->toHTML();
    }

    /**
     * Create and return WP_Query object for the search instance
     *
     * @return WP_Query
     */
    public function query() {
        $query = $this->factory->buildQueryObject();
        if (!$this->ajaxEnabled()) $this->print_debug();
        return $query;
    }

    /**
     * Displays range of results displayed on the current page.
     *
     * @return string
     */
    function results_range( $args = array() ) {
        global $wp_query;

        $defaults = array(
            'pre' => '',
            'marker' => '-',
            'post' => ''
        );

        $args = wp_parse_args($args, $defaults);
        extract($args);

        $total = $wp_query->found_posts;
        $count = $wp_query->post_count;
        $query = $wp_query->query;
        $ppp = (!empty($query['posts_per_page'])) ? $query['posts_per_page'] : get_option('posts_per_page');
        $page = get_query_var('paged');

        $range = $page;
        if ($ppp > 1) {
            $i = 1 + (($page - 1)*$ppp);
            $j = $i + ($ppp - 1);
            $range = sprintf('%d%s%d', $i, $marker, $j);
            if ($j > $total) {
                $range = $total;
            }
        }

        if ($count < 1) {
            $range = 0;
        }

        $output = sprintf('<span>%s</span> <span>%s</span> <span>%s</span>', $pre, $range, $post);

        return $output;
    }

    /**
     * Displays pagination links
     */
    public function pagination( $args = '', $return = false ) {
        global $wp_query;
        echo $this->get_pagination($wp_query, $args);
    }

    public function get_pagination($query_object, $args = '') {
        global $wp_query;
        $temp = $wp_query;

        $wp_query = $query_object;

        $output = "";

        $current_page = max(1, get_query_var('paged'));
        $total_pages = $wp_query->max_num_pages;

        $big = '999999999';
        $base = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );

        $defaults = array(
            'base' => $base,
            'format' => 'page/%#%',
            'current' => $current_page,
            'total' => $total_pages
        );

        $args = wp_parse_args($args, $defaults);

        if ($total_pages > 1){
            $output .=  '<div class="pagination">';
            $output .= paginate_links($args);
            $output .=  '</div>';
        }

        $wp_query = $temp;

        return $output;
    }

    /**
     * Create string of debug information
     *
     * For use when WPAS_DEBUG is enabled, or when calling the
     * debug() method.
     *
     * When $log is set to 'verbose', the output will contain a full var dump
     * of the generated WP_Query object.
     *
     * @param string $level
     * @return string
     */
    public function create_debug_output($level = 'log') {
        $errors = $this->get_errors();
        $wp_query_obj = $this->factory->getWPQuery();


        $output = "WPAS DEBUG\n";

        $output .= "------------------------------------\n";
        $output .= "|| Errors\n";
        $output .= "------------------------------------\n";

        if (empty($errors)) {
            $output .= "No errors detected.\n";
        } else {
            $output .= count($errors) . " errors detected:\n";
            $output .= print_r($errors, true) . "\n";
        }

        $output .= "------------------------------------\n";
        $output .= "|| WP_Query Arguments\n";
        $output .= "------------------------------------\n";

        $output .= print_r($wp_query_obj->query, true) . "\n";

        $output .= "------------------------------------\n";
        $output .= "|| MySQL Query \n";
        $output .= "------------------------------------\n";

        $output .= print_r($wp_query_obj->request, true) . "\n";

        $output .= "------------------------------------\n";
        $output .= "|| Request Data \n";
        $output .= "------------------------------------\n";

        $output .= print_r($this->request, true) . "\n";

        if ($level == 'verbose') {
            $output .= "------------------------------------\n";
            $output .= "|| WP_Query Object Dump\n";
            $output .= "------------------------------------\n";
            $output .= print_r($wp_query_obj, true);
        }

        return $output;
    }

    /**
     * Print debug information
     */
    public function print_debug() {
        if (!defined('WPAS_DEBUG') || !WPAS_DEBUG) return;
        $log_level = (defined('WPAS_DEBUG_LEVEL')) ? WPAS_DEBUG_LEVEL : 'log';
        $output = $this->create_debug_output($log_level);
        echo '<pre>' . $output . '</pre>';
    }


    /**
     * Get array of errors generated during setup/configuration of search
     * instance
     *
     * @return array
     */
    public function get_errors() {
        $errors = $this->errors;
        if (is_object($this->factory)) {
            $errors = array_merge($this->errors, $this->factory->getErrors());
        }
        return $errors;
    }

    private function ajaxEnabled() {
        $args = $this->args;
        return (!empty($args) && !empty($args['ajax']) && $args['ajax']);
    }

}