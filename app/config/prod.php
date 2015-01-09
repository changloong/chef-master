<?php

// env
$app['env'] = 'prod' ;
$app['env.user'] = 'www-data' ;
$app['env.group'] = 'www-data' ;
$app['env.php_binary'] = $app->share(function() use($app) {
    $finder = new \Symfony\Component\Process\PhpExecutableFinder();
    return $finder->find() ;
});
$app['root.dir'] = __DIR__ . '/../..' ;

$app['locale'] = 'zh_CN';

// Cache
$app['cache.path'] = __DIR__ . '/../cache';

// doctrine

$app['db.options'] = array(
    'driver'   => 'pdo_mysql',
    'host'     => 'localhost',
    'dbname'   => 'gmc_app',
    'user'     => 'root',
    'password' => '',
);

// knife.env
$app['knife.env.current_path']  = $app['cache.path'] . '/knife.env_current' ;

$app['knife.script.vars']  = array (
    'git_deploy'   => array(
        'local' => $app['root.dir'] . '/app/config/keys/git_deploy.pem' ,
        'remote' => '~/.chefsolo/config/.deploy_key.pem' ,
    ) ,

    'chef_url'  => array (
        'rpm'   =>  'http://192.168.10.20/chef/chef-11.14.6-1.el6.x86_64.rpm' ,
        'deb'   =>  'http://192.168.10.20/chef/chef_11.14.6-1_amd64.deb' ,
    ) ,
) ;

if( file_exists( __DIR__ . '/local.php') ) {
    require_once __DIR__ . '/local.php';
}