<?php
namespace WPAS;

abstract class StdObject {

    protected $args;
    protected $errors;
    protected static $rules;

    //abstract public function validate();

    protected function validationErrorMsg( array $errors ) {
        $err_msg = "Validation of object ". get_class($this) . 
                                            " failed. ". implode(" ",$errors);
        return $err_msg;
    }

    protected function parseArgs(array $args, array $defaults) {

        if ( is_object( $args ) )
            $r = get_object_vars( $args );
        else
            $r =& $args;

        if ( is_array( $defaults ) )
            return array_merge( $defaults, $r );
        return $r;
    }

}
