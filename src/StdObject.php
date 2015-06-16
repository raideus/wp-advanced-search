<?php
namespace WPAS;

abstract class StdObject {

    protected static $rules;
    protected static $defaults;
    private static $constCacheArray = NULL;
    private static $methodCacheArray = NULL;

    public static function getConstants() {
        if (self::$constCacheArray == NULL) {
            self::$constCacheArray = array();
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    protected static function getMethods() {
        if (self::$methodCacheArray == NULL) {
            self::$methodCacheArray = array();
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$methodCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$methodCacheArray[$calledClass] = $reflect->getMethods();
        }
        return self::$methodCacheArray[$calledClass];
    }

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
