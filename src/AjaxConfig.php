<?php
namespace WPAS;

class AjaxConfig extends StdObject
{
    private $enabled;
    private $loading_img;
    private $button_text;
    protected $args;

    static protected $rules = array(
        'enabled' => 'bool',
        'loading_img' => 'string',
        'mode' => 'string',
        'button_text' => 'string',
    );

    static protected $defaults = array(
        'enabled' => false,
        'loading_img' => 'default',
        'mode' => 'lazy-load',
        'button_text' => 'LOAD MORE RESULTS',
    );

    function __construct($args = array())
    {
        $this->args = $this->parseArgs($args, self::$defaults);

        foreach ($this->args as $key => $value) {
            $this->$key = $value;
        }

        if ($this->loading_img == 'default') {
            $this->loading_img = get_template_directory_uri() . '/' . basename(dirname(dirname(__FILE__))) . '/img/loading.gif';
        } else {
            die($this->loading_img);
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
    public function mode()
    {
        return $this->mode;
    }

    /**
     * @return mixed
     */
    public function buttonText()
    {
        return $this->button_text;
    }
}