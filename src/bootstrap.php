<?php

use Silex\Provider\MonologServiceProvider;

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' =>   __DIR__.'/../app/logs/app_' . $app['env'] . '.log' ,
    'monolog.name'    => 'app',
    'monolog.level'   => 300 // = Logger::WARNING
));

$app->register(new \Knp\Provider\ConsoleServiceProvider(), array(
    'console.name'              => 'Sex8Master',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__ . '/..' ,
));

// Doctrine DBAL
$app->register(new Silex\Provider\DoctrineServiceProvider());

// Doctrine ORM
$app->register(new \App\Cloud\Provider\DoctrineORMServiceProvider(), array(
    'db.orm.proxies_dir'           => __DIR__ . '/cache/orm_proxies_dir' ,
    // 'db.orm.cache'                 => null ,
    'db.orm.proxies_namespace'     => 'DoctrineProxy',
    'db.orm.auto_generate_proxies' => true ,
    'db.orm.entities'              => array(
        array(
            'type' => 'annotation',
            'namespace' => 'App\Cloud\Entity' ,
            'path'  => __DIR__ . '/App/Cloud/Entity' ,
        ),
    ),
));

$app->register(new App\Cloud\Provider\ServiceProvider(),array(
    // 'app.annotation.cache.dir'   => $app['cache.path'] . '/annotation_cache' ,
));

$app->register(new App\Cloud\Provider\ScriptServiceProvider(),array(
    'app.twig.options'   => array(
        // 'cache' => $app['cache.path'] . '/scripts_' . $app['env'] ,
    ) ,
));




\App::Init($app) ;

return $app;
