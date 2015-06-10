<?php
namespace WPAS;

class AjaxConfig extends StdObject
{
    private $enabled;
    private $loading_img;
    private $button_text;
    private $mode;
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
        $this->args = $this->parseArgs($args, self::$defaults);

        foreach ($this->args as $key => $value) {
            $this->$key = $value;
        }

        if (empty($this->loading_img)) {
            $this->loading_img = get_template_directory_uri() . '/' . basename(dirname(dirname(__FILE__))) . '/img/loading.gif';
        }

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
    public function showDefaultResults()
    {
        return $this->show_default_results;
    }
}