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
    private $close_img_html;
    protected $args;

    static protected $rules = array(
        'enabled' => 'bool',
        'loading_img' => 'string',
        'button_text' => 'string',
        'show_default_results' => 'bool',
        'results_template' => 'string',
        'url_hash' => 'string',
        'close_img_html' => 'string',
    );

    static protected $defaults = array(
        'enabled' => false,
        'button_text' => 'LOAD MORE RESULTS',
        'show_default_results' => true,
        'url_hash' => 'results',
        );
        //'close_img' => 

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

        if(empty($args['close_img_html'])){
            $args['close_img_html'] = "<svg style='display:inline; cursor:pointer;' class='filters-close'  width='12' height='12' viewBox='0 0 32 32'><path class='path1' d='M31.708 25.708c-0-0-0-0-0-0l-9.708-9.708 9.708-9.708c0-0 0-0 0-0 0.105-0.105 0.18-0.227 0.229-0.357 0.133-0.356 0.057-0.771-0.229-1.057l-4.586-4.586c-0.286-0.286-0.702-0.361-1.057-0.229-0.13 0.048-0.252 0.124-0.357 0.228 0 0-0 0-0 0l-9.708 9.708-9.708-9.708c-0-0-0-0-0-0-0.105-0.104-0.227-0.18-0.357-0.228-0.356-0.133-0.771-0.057-1.057 0.229l-4.586 4.586c-0.286 0.286-0.361 0.702-0.229 1.057 0.049 0.13 0.124 0.252 0.229 0.357 0 0 0 0 0 0l9.708 9.708-9.708 9.708c-0 0-0 0-0 0-0.104 0.105-0.18 0.227-0.229 0.357-0.133 0.355-0.057 0.771 0.229 1.057l4.586 4.586c0.286 0.286 0.702 0.361 1.057 0.229 0.13-0.049 0.252-0.124 0.357-0.229 0-0 0-0 0-0l9.708-9.708 9.708 9.708c0 0 0 0 0 0 0.105 0.105 0.227 0.18 0.357 0.229 0.356 0.133 0.771 0.057 1.057-0.229l4.586-4.586c0.286-0.286 0.362-0.702 0.229-1.057-0.049-0.13-0.124-0.252-0.229-0.357z'></path></svg>";
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

    public function closeImg(){
        return $this->close_img_html;
    }

}