<?php

require_once __DIR__.'/../vendor/autoload.php';

Symfony\Component\Debug\Debug::enable();

$app = new Silex\Application();
require __DIR__.'/../app/config/prod.php';
require __DIR__.'/../src/bootstrap.php';

$app['http_cache']->run();

