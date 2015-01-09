<?php

namespace App\Composer;

class Script
{
    public static function install()
    {

        $finder = new \Symfony\Component\Process\PhpExecutableFinder();
        self::php_exec($finder->find(), './app/console knife:setup');
    }

    public static function setup(\Silex\Application $app ){

        $root_dir    = $app['cache.path'] . '/../' ;
        if( ! @chdir( $root_dir ) ) {
            throw new \Exception( sprintf("chdir `%s` error!", $root_dir ));
        }

        self::fixpath('cache', 0755);
        self::fixpath('logs', 0755);

        self::fixpath('app/console', 0755);

        $is_windows = DIRECTORY_SEPARATOR == '\\' ;

        if( $is_windows ) {
            self::exec('del /S /F /Q .\\cache\\*');
        } else {
            self::exec('rm -r ./cache/*');
        }
        
        self::php_exec( $app['env.php_binary'], './console cache:clear', $app['env'] );
        self::php_exec( $app['env.php_binary'], './console assetic:dump', $app['env'] );

        if( !$is_windows ) {
            self::exec( sprintf('chown -R %s:%s ./src ./app', $app['env.user'], $app['env.group']) );
        }
    }

    private static function exec($_cmd) {
        $ret    = null ;
        echo sprintf('>>>: %s', $_cmd), "\n" ;
        passthru($_cmd, $ret);
    }

    private static function php_exec($php, $cmd, $env = null ) {
        $_cmd   = sprintf('%s %s', $php, $cmd) ;
        if( $env ) {
            $_cmd .= ' --env=' . $env ;
        }
        $ret    = null ;
        echo sprintf('>>>: %s', $_cmd), "\n" ;
        passthru($_cmd, $ret);
    }
    
    private static function fixpath($dir, $mode) {
        if( !file_exists($dir) ) {
            if( !@mkdir( $dir, $mode ) ) {
                throw new \Exception( sprintf("can not mkdir `%s`", $dir) );
            }
        } else {
            chmod( $dir, $mode );
        }
    }
}
