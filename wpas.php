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

class WP_Advanced_Search {
    private $factory;

    function __construct($args = '') {
        $this->factory = new WPAS\Factory($args, $_REQUEST);
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
        $this->print_debug();
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
    public function pagination( $args = '' ) {
        global $wp_query;
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
            echo '<div class="pagination">';
            echo paginate_links($args);
            echo '</div>';
        }

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
    private function create_debug_output($level = 'log') {
        $errors = $this->factory->getErrors();
        $wp_query_obj = $this->factory->getWPQuery();


        $output = "WPAS DEBUG\n";

        $output .= "------------------------------------\n";
        $output .= "|| Errors\n";
        $output .= "------------------------------------\n";

        if (empty($errors)) {
            $output .= "No errors detected.\n";
        } else {
            $output .= count($errors) . " errors detected:\n";
            $output .= print_r($this->factory->getErrors(), true) . "\n";
        }

        $output .= "------------------------------------\n";
        $output .= "|| WP_Query Arguments\n";
        $output .= "------------------------------------\n";

        $output .= print_r($wp_query_obj->query, true) . "\n";

        $output .= "------------------------------------\n";
        $output .= "|| MySQL Query \n";
        $output .= "------------------------------------\n";

        $output .= print_r($wp_query_obj->request, true) . "\n";

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
    public function errors() {
        return $this->factory->getErrors();
    }

}