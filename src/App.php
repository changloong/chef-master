<?php

final class App {

    /**
     * @var \Silex\Application
     */
    static private $app;

    static public function Init(\Silex\Application $app ) {
        if (null !== self::$app) {
            return ;
        }
        self::$app          = $app ;
    }

    static private $dynamic_cache   = array() ;

    public static function get($key){
        if( !isset(self::$dynamic_cache[$key]) ) {
            throw new \Exception( sprintf('%s not exists for App::$dynamic_cache', $key));
        }
        return self::$dynamic_cache[$key] ;
    }

    public static function set( $key, $value ){
        self::$dynamic_cache[$key] = $value ;
    }

    public static function last_error(){
        $error = error_get_last() ;
        if( !$error ) {
            return null ;
        }
        if( is_array($error) ) {
            return \Dev::format_var($error) ;
        }
        return $error ;
    }

}