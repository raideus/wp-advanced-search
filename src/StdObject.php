<?php
namespace WPAS;

abstract class StdObject {
    protected static $rules;
    protected static $defaults;

    /**
     * Validate form arguments
     *
     * @param $args
     * @param $defaults
     * @return array
     * @throws \Exception
     */
    protected static function validate($args) {
        $validation = new Validator(static::$rules, $args, static::$defaults);
        if ($validation->fails()) {
            $errors = $validation->getErrors();
            $err_msg = self::validationErrorMsg($errors);
            throw new \Exception($err_msg);
        }
        return $validation->getArgs();
    }

    protected static function validationErrorMsg( array $errors ) {
        $err_msg = 'Validation of object '. get_called_class() .
                                            ' failed. '. implode(" ",$errors);
        return $err_msg;
    }

    protected static function parseArgs(array $args, array $defaults) {
        if ( is_object( $args ) )
            $r = get_object_vars( $args );
        else
            $r =& $args;

        if ( is_array( $defaults ) )
            return array_merge( $defaults, $r );
        return $r;
    }

}
