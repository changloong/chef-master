<?php

// include the prod configuration
require __DIR__ . '/prod.php';

$app['env'] = 'dev' ;

// enable the debug mode
$app['debug'] = true;
