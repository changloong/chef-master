<?php

require_once __DIR__.'/../vendor/autoload.php';

Symfony\Component\Debug\Debug::enable();

$app = new Silex\Application();
require __DIR__.'/../app/config/test.php';
require __DIR__.'/../src/bootstrap.php';

$app->run();
