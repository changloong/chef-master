<?php

// include the prod configuration
require __DIR__ . '/prod.php';

$app['env'] = 'test' ;

// enable the debug mode
$app['debug'] = true;
