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
                                    'id' => 'string',
                                    'name' => 'string',
                                    'class' => 'array<string>',
                                    'ajax' => 'object',
                                    'disable_wrappers' => 'bool',
                                    'auto_submit' => 'bool',
                                    'inputs' => 'array' );

    static protected $defaults = array(  
                                        'action' => '',
                                        'method' => 'GET',
                                        'id' => 'wp-advanced-search',
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
                class=\"".$this->getClassString()."\"
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

        return $output;
    }

    public function addClass($class) {
        if (!is_string($class)) return;
        $this->class[] = $class;
    }

    public function disableWrappers() {
        return ((defined('WPAS_DISABLE_WRAPPERS') && WPAS_DISABLE_WRAPPERS) || ($this->disable_wrappers));
    }

    public function getID() {
        return $this->id;
    }

    public function getAction() {
        return $this->action;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getName() {
        return $this->name;
    }

    public function getClass() {
        return $this->class;
    }

    public function getClassString() {
        $str = implode(" ",$this->class);
        if ($this->ajax->isEnabled()) {
            $str .= " wpas-ajax-enabled";
        }
        if ($this->auto_submit) {
            $str .= " wpas-autosubmit";
        }
        return $str;
    }

    public function getInputs() {
        return $this->inputs;
    }

    public function getDefaults() {
        return self::$defaults;
    }



}
