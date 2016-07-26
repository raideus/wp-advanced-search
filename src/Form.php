<?php 
namespace WPAS;

class Form extends StdObject {
    private $wpas_id;
    private $id;
    private $action;
    private $method;
    private $name;
    private $class;
    private $inputs;
    private $ajax;
    private $disable_wrappers;
    private $auto_submit;
    protected $args;

    static protected $rules = array(
                                    'action' => 'string',
                                    'method' => 'RequestMethod',
                                    'name' => 'string',
                                    'class' => 'array<string>',
                                    'ajax' => 'object',
                                    'disable_wrappers' => 'bool',
                                    'auto_submit' => 'bool',
                                    'inputs' => 'array' );

    static protected $defaults = array(  
                                        'action' => '',
                                        'method' => 'GET',
                                        'name' => 'wp-advanced-search',
                                        'class' => array('wp-advanced-search'),
                                        'disable_wrappers' => false,
                                        'auto_submit' => false,
                                        'inputs' => array() );

    function __construct($wpas_id, $args) {
        $this->wpas_id = $wpas_id;
        $args = $this->preProcessArgs($args);
        $args = $this->parseArgs($args, self::$defaults);
        $this->args = self::validate($args);

        foreach($this->args as $key => $value) {
            $this->$key = $value;
        }

        $this->id = "wp-advanced-search";
    }

    /**
     * Returns the full HTML content of the form
     *
     * @return string
     */
    public function toHTML() {
        global $post;

        $output = "
        <form id=\"".$this->id."\" name=\"".$this->name."\" 
                class=\"".$this->classString()."\"
                method=\"".$this->method."\" ";
        $output .= $this->dataAttributesString();
        $output .= "action=\"".$this->action."\"> ";

        // URL fix if "pretty permalinks" are not enabled
        if ( get_option('permalink_structure') == '' && is_object($post) ) {
            $output .= '<input type="hidden" name="page_id" value="'.$post->ID.'">';
        }

        foreach ($this->inputs as $input) {
            if ($input instanceof Input) {
                $output .= $input->toHTML();
            }
        }

        if ($this->ajax->isEnabled()) {
            $output .= "<input type=\"hidden\" id=\"wpas-paged\" name=\"paged\" value=\"1\">";
        }

        $output .= "<input type=\"hidden\" id=\"wpas-id\" name=\"wpas_id\" value=\"".$this->wpas_id."\">";
        $output .= "<input type=\"hidden\" name=\"wpas_submit\" value=\"1\">";

        $output .= "</form>";

        return $output;
    }

    /**
     * Add an Input object to the form
     *
     * @param Input $input
     */
    public function addInput( Input $input ) {
        if ($this->disableWrappers()) $input->disableWrapper();
        $this->inputs[] = $input;
    }

    /**
     * Process and return arguments
     *
     * @param $args
     * @return mixed
     */
    private function preProcessArgs($args) {
        if (!empty($args['class']) && is_string($args['class'])) {
            $args['class'] = explode(' ', $args['class']);
        }
        if (empty($args['ajax'])) $args['ajax'] = new AjaxConfig();
        return $args;
    }

    /**
     * Generates string of data attributes
     *
     * @return string
     */
    private function dataAttributesString() {
        $output = "";
        if ($this->ajax->isEnabled() == false) return $output;

        $output .= "data-ajax-button=\"".$this->ajax->buttonText()."\" ";
        $output .= "data-ajax-loading=\"".$this->ajax->loadingImage()."\" ";
        $show_default = ($this->ajax->showDefaultResults()) ? "1" : "0";
        $output .= "data-ajax-show-default=\"".$show_default."\" ";
        $output .= "data-ajax-url-hash=\"".$this->ajax->urlHash()."\" ";

        return $output;
    }

    private function classString() {
        $str = implode(" ",$this->class);
        if ($this->ajax->isEnabled()) {
            $str .= " wpas-ajax-enabled";
        }
        if ($this->auto_submit) {
            $str .= " wpas-autosubmit";
        }
        return $str;
    }

    public function addClass($class) {
        if (!is_string($class)) return;
        $this->class[] = $class;
    }

    public function disableWrappers() {
        return ((defined('WPAS_DISABLE_WRAPPERS') && WPAS_DISABLE_WRAPPERS) || ($this->disable_wrappers));
    }


    public function getInputs() {
        return $this->inputs;
    }

    public function getDefaults() {
        return self::$defaults;
    }

    public function getAttributes() {
        return array(   'id' => $this->id,
                        'class' => $this->class,
                        'method' => $this->method,
                        'action' => $this->action,
                        'name' => $this->name );
    }

}
