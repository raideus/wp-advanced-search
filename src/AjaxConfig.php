<?php
namespace WPAS;

class AjaxConfig extends StdObject
{
    private $enabled;
    private $loading_img;
    private $button_text;
    private $show_default_results;
    private $results_template;
    protected $args;

    static protected $rules = array(
        'enabled' => 'bool',
        'loading_img' => 'string',
        'button_text' => 'string',
        'show_default_results' => 'bool'
    );

    static protected $defaults = array(
        'enabled' => false,
        'button_text' => 'LOAD MORE RESULTS',
        'show_default_results' => true
    );

    function __construct($args = array())
    {
        $args = $this->parseArgs($args, self::$defaults);
        $args = $this->processArgs($args);
        $this->args = $args;

        foreach ($this->args as $key => $value) {
            $this->$key = $value;
        }

    }

    private function processArgs(array $args) {
        $dir = basename(dirname(dirname(__FILE__)));

        if (empty($args['loading_img'])) {
            $args['loading_img'] = get_template_directory_uri() . '/' . $dir . '/img/loading.gif';
        }

        if (empty($args['results_template'])) {
            $args['results_template'] = $dir . '/templates/template-ajax-results.php';
        }

        return $args;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return mixed
     */
    public function loadingImage()
    {
        return $this->loading_img;
    }

    /**
     * @return mixed
     */
    public function buttonText()
    {
        return $this->button_text;
    }

    /**
     * @return mixed
     */
    public function resultsTemplate()
    {
        return $this->results_template;
    }

    /**
     * @return mixed
     */
    public function showDefaultResults()
    {
        return $this->show_default_results;
    }

}