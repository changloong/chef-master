<?php
namespace App\Cloud\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

use Doctrine\Common\Annotations;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Cache;

use Knp\Console\ConsoleEvents;
use Knp\Console\ConsoleEvent;

class ServiceProvider implements ServiceProviderInterface {

    public function register(Application $app) {
        ////
        // Absolute dependencies
        ////
        $app->register(new ServiceControllerServiceProvider());

        ////
        // User Configured Values
        ////
        $app['app.annotation.cache.dir']       = null;

        ////
        // Internal Services
        ////
        $app['app.annotation.cache'] = $app->share(function($app){
            $cache_dir = $app['app.annotation.cache.dir'];

            if (!$cache_dir) return false;

            $cache = new Cache\FilesystemCache($cache_dir);

            return $cache;
        });

        $app['app.annotation.reader'] = $app->share(function($app) {

            $reader = new Annotations\AnnotationReader();
            if ($cache = $app['app.annotation.cache']) {
                $reader = new Annotations\CachedReader($reader, $cache);
            }

            return $reader;
        });

        $app['app.annotation.directoryloader'] = $app->share(function() {
            return new \App\Cloud\AnnotationManager\Loader\DirectoryLoader() ;
        });

        $app['app.cf'] = $app->share(function($app) {
            return new \App\Cloud\Configuration\ConfigurationFactory($app) ;
        });

        $app['app.cm'] = $app->share(function($app) {
            return new \App\Cloud\CloudManger($app) ;
        });
    }

    public function boot(Application $app) {

        AnnotationRegistry::registerAutoloadNamespace('App\Cloud\Annotation', __DIR__ . '/../../../' ) ;

        $compiler = new \App\Cloud\AnnotationManager\Compiler\ConfigurationCompiler($app['app.annotation.reader'], $app['app.annotation.directoryloader']);
        $compiler->setContainer($app);
        $dir    = __DIR__ . '/../Configuration' ;
        $compiler->compile( $dir ) ;

        $app['app.cf']->afterCompilerPass() ;

        $compiler = new \App\Cloud\AnnotationManager\Compiler\ControllerCompiler($app['app.annotation.reader'], $app['app.annotation.directoryloader']);
        $compiler->setContainer($app);
        $compiler->compile( __DIR__ . '/../../Controller') ;

        $app['dispatcher']->addListener(ConsoleEvents::INIT, function(ConsoleEvent $event) use ($app) {

            $compiler = new \App\Cloud\AnnotationManager\Compiler\CommandCompiler($app['app.annotation.reader'], $app['app.annotation.directoryloader']);
            $compiler->setContainer($app);
            $compiler->setConsole( $event->getApplication() ) ;
            $compiler->compile( __DIR__ . '/../../Command') ;

        });


    }
}
