<?php
namespace WPAS;

class AjaxConfig extends StdObject
{
    private $enabled;
    private $loading_img;
    private $button_text;
    private $show_default_results;
    private $results_template;
    private $url_hash;
    protected $args;

    static protected $rules = array(
        'enabled' => 'bool',
        'loading_img' => 'string',
        'button_text' => 'string',
        'show_default_results' => 'bool',
        'results_template' => 'string',
        'url_hash' => 'string'
    );

    static protected $defaults = array(
        'enabled' => false,
        'button_text' => 'LOAD MORE RESULTS',
        'show_default_results' => true,
        'url_hash' => 'results'
    );

    function __construct($args = array())
    {
        $args = $this->parseArgs($args, self::$defaults);
        $args = self::validate($args);
        $args = $this->processArgs($args);
        $this->args = $args;

        foreach ($this->args as $key => $value) {
            $this->$key = $value;
        }

    }

    private function processArgs(array $args) {
        $dir = basename(dirname(dirname(__FILE__)));

        if (empty($args['loading_img'])) {
            $args['loading_img'] = get_wpas_uri() . '/img/loading.gif';
        }

        if (empty($args['results_template'])) {
            $args['results_template'] = $dir . '/templates/template-ajax-results.php';
        }

        return $args;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function loadingImage()
    {
        return $this->loading_img;
    }

    public function buttonText()
    {
        return $this->button_text;
    }

    public function resultsTemplate()
    {
        return $this->results_template;
    }

    public function showDefaultResults()
    {
        return $this->show_default_results;
    }

    public function urlHash() {
        return $this->url_hash;
    }

}