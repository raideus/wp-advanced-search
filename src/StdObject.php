<?php
namespace WPAS;

abstract class StdObject {

    protected $args;
    protected $errors;
    protected static $rules;
    private static $constCacheArray = NULL;
    private static $methodCacheArray = NULL;

    protected static function getConstants() {
        if (self::$constCacheArray == NULL) {
            self::$constCacheArray = [];
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
            self::$methodCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$methodCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$methodCacheArray[$calledClass] = $reflect->getMethods();
        }
        return self::$methodCacheArray[$calledClass];
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
