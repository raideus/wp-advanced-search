<?php 
namespace WPAS;
require_once('StdObject.php');
require_once('Validator.php');
require_once('Exceptions.php');

class Form extends StdObject {
    private $id;
    private $action;
    private $method;
    private $name;
    private $class;
    private $inputs;
    protected $args;
    static protected $rules = array(
                                    'action' => 'string',
                                    'method' => 'FormMethod',
                                    'id' => 'string',
                                    'name' => 'string',
                                    'class' => 'array<string>',
                                    'inputs' => 'array' );
    static protected $defaults = array(  
                                        'action' => '',
                                        'method' => 'GET',
                                        'id' => 'wp-advanced-search',
                                        'name' => 'wp-advanced-search',
                                        'class' => array('wp-advanced-search'),
                                        'inputs' => array() );

    function __construct( $args ) {
        $args = $this->preProcessArgs($args);
        $args = $this->parseArgs($args, self::$defaults);
        $this->args = $this->validate($args, self::$defaults);

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

        if (!is_array($this->class)) {
            echo "Not an array!" . gettype($this->class);
            die;
        }

        $output = "
        <form id=\"".$this->id."\" name=\"".$this->name."\" 
                class=\"".implode(" ",$this->class)."\"  
                method=\"".$this->method."\"  
                action=\"".$this->action."\"> ";

        // URL fix if "pretty permalinks" are not enabled
        if ( get_option('permalink_structure') == '' && is_object($post) ) {
            $output .= '<input type="hidden" name="page_id" value="'.$post->ID.'">';
        }

        foreach ($this->inputs as $input) {
            if ($input instanceof Input) {
                $output .= $input->toHTML();
            }
        }

        $output .= "</form>";

        return $output;
    }

    public function addInput( Input $input ) {
        $this->inputs[] = $input;
    }

    public function validate($args, $defaults) {
        $validation = new Validator(self::$rules, $args, $defaults);
        if ($validation->fails()) {
            $errors = $validation->getErrors();
            $err_msg = $this->validationErrorMsg($errors);
            throw new ValidationException($err_msg);
        }

        return $validation->getArgs();
    }

    private function preProcessArgs($args) {
        if (!empty($args['class']) && is_string($args['class'])) {
            $args['class'] = explode(' ', $args['class']);
        }
        return $args;
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

    public function getInputs() {
        return $this->inputs;
    }

    public function getDefaults() {
        return self::$defaults;
    }



}
