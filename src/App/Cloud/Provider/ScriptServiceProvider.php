<?php

namespace App\Cloud\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Form\TwigRenderer;

/**
 * Twig integration for Silex.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ScriptServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {

        if( !isset($app['app.twig.options']) ) {
            $app['app.twig.options']    = array() ;
        }
        if( !isset($app['app.twig.templates']) ) {
            $app['app.twig.templates']  = array() ;
        }

        $app['app.script.manager'] = $app->share(function($app) {
            return new \App\Cloud\Script\ScriptManger($app) ;
        });

        $app['app.twig'] = $app->share(function ($app) {
            $app['twig.options'] = array_replace(
                array(
                    'debug'            => $app['debug'] ,
                    'auto_reload' => $app['debug'] ,
                    'cache' => false ,
                ), $app['app.twig.options']
            ) ;
            $twig = new \Twig_Environment($app['app.twig.loader'], $app['app.twig.options']);
            $twig->addGlobal('app', $app) ;
            return $twig ;
        });

        $app['app.twig.loader.filesystem'] = $app->share(function ($app) {
            return new \Twig_Loader_Filesystem( sprintf('%s/app/Resources/scripts', $app['root.dir']) ) ;
        });

        $app['app.twig.loader.array'] = $app->share(function ($app) {
            return new \Twig_Loader_Array($app['app.twig.templates']);
        });

        $app['app.twig.loader'] = $app->share(function ($app) {
            return new \Twig_Loader_Chain(array(
                $app['app.twig.loader.array'] ,
                $app['app.twig.loader.filesystem'] ,
            )) ;
        });
    }

    public function boot(Application $app)
    {

    }
}
