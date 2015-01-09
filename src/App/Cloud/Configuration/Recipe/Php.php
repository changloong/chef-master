<?php

namespace App\Cloud\Configuration\Recipe;

/**
 * @App\Cloud\Annotation\Config("php")
 */
class Php extends \App\Cloud\Configuration\RecipeParent {

    protected function configPhpInstallNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node){

        $node
            ->append(
                $this->createNode('php_src_url')->defaultValue('http://192.168.10.20/chef/php/php-5.5.16.tar.gz')
            )
            ->append(
                $this->createNode('php_src_version')->defaultValue('5.5.16')
            )
            ->append(
                $this->createNode('php_src_checksum')->defaultValue('5def6d89792caa70448c67cd510e0f3e')
            )
            ->append(
                $this->createNode('prefix_dir')
                    ->defaultValue('/opt/local/php55')
            )
        ;
    }

    protected function configExtensionsNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node){

        $add    = function($name, $src, $pkg, array $defaults = null, $options = null) use($node){
            $node
                ->children()
                    ->arrayNode($name)
                        ->addDefaultsIfNotSet()
                        ->appendWith( function($node) use($src, $pkg, $options ){
                            $node
                                ->append(
                                    $this->createNode('enable', 'boolean')->defaultValue(true)
                                )
                            ;
                            if( $src) {
                                $node
                                    ->append(
                                        $this->createNode('src_url')->defaultValue($src)
                                    );
                                $node
                                    ->append(
                                        $this->createNode('src_package')->defaultValue($pkg)
                                    )
                                    ->append(
                                        $this->createNode('src_options')->defaultValue($options)
                                    );

                            }
                        })
                        ->children()
                            ->appendIf(
                                self::createIniNode($this, $defaults ) ,
                                null !== $defaults
                            )
                        ->end()
                    ->end()
                ->end()
            ;
        };

        $add('opcache', null , null , array(
            'enable'    => true ,
            'enable_cli'    => true ,
            'memory_consumption'    => 32 ,
        ) );

        $add('memcached', 'http://192.168.10.20/chef/php/memcached-2.2.0.tgz', 'memcached-2.2.0', array(
            'default_port'    => 11211 ,
        ) , '--disable-memcached-sasl' );

        $add('redis', 'http://192.168.10.20/chef/php/phpredis.tar', 'phpredis', array(

        )  );

        $add('xhprof', 'http://192.168.10.20/chef/php/xhprof.tar', 'xhprof', array(

        )  );

        $add('mongo', 'http://192.168.10.20/chef/php/mongo-php-driver.tar', 'mongo-php-driver', array(

        )  );
        $add('twig', 'http://192.168.10.20/chef/php/twig.tar', 'twig',  null );

        $add('mysqlnd_ms', 'http://192.168.10.20/chef/php/mysqlnd_ms-1.6.0.tgz', 'mysqlnd_ms-1.6.0', array(

        )  );

        $add('apcu', 'http://192.168.10.20/chef/php/apcu-4.0.6.tgz', 'apcu-4.0.6', array(
            'enabled'   => 'on' ,
        )  ) ;

        $add('ssh2', 'http://192.168.10.20/chef/php/ssh2-0.12.tgz', 'ssh2-0.12') ;

    }

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        $root
            ->children()
                ->arrayNode( 'service')
                    ->addDefaultsIfNotSet()
                    ->appendWith( function($node){
                        $this->configPhpInstallNode($node) ;
                    })
                ->end()
                ->arrayNode( 'extensions')
                    ->addDefaultsIfNotSet()
                    ->appendWith( function($node){
                        $this->configExtensionsNode($node) ;
                    })
                ->end()
            ->end()
        ;

        $root
            ->children()
                ->append(
                    self::createIniNode($this, array(
                        'post_max_size'  => '8M' ,
                        'upload_max_filesize'  => '8M' ,
                        'session' => array(
                            'name'  => 'PSID' ,
                        ) ,
                        'date' => array(
                            'timezone' => 'UTC' ,
                        ),
                        'zlib' => array(
                            'output_compression'    => 'off' ,
                            'output_compression_level'  => -1 ,
                        ),
                    ) )->requiresAtLeastOneElement()
                )
            ->end()
        ;

        PhpPool::configPhpPoolRootNode($root, $this, array(

        ), true ) ;
    }

    private static function createIniNode(\App\Cloud\Configuration\Recipe $self, array $default = null ){
        $ini = $self->createNode('ini', 'array') ;

        $ini
            ->prototype('variable')
            ->end()
        ;
        if( $default ) {
            $ini->defaultValue( $default ) ;
        }
        return $ini ;
    }

    public function getRecipeName(){
        return 'fpm' ;
    }

    public function getChildRecipeName() {
        return 'php_pool' ;
    }

    protected function onRecipeExecute(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false) {

        $access     = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false );
        $dir        = $access->getValue($config, '[service][prefix_dir]' ) ;
        $tmp_dir    = sprintf('%s/tmp', $dir) ;
        $access->setValue($config, '[service][tmp_dir]', $tmp_dir) ;
        $tmp_dirs   = array(
            '[ini][upload_tmp_dir]' ,
            '[ini][session][save_path]' ,
            '[ini][soap][wsdl_cache_dir]',
            '[ini][apc][writable]' ,
        );
        foreach($tmp_dirs as $path) {
            $access->setValue($config, $path, $tmp_dir) ;
        }


        if( isset($config['extensions']) && is_array($config['extensions']) ) {

            if( $config['extensions']['memcached']['enable'] ) {
                $memcached_recipe   = $this->_cloud_manger->getRecipeByName('memcached', $recipe->getClient() );
                if( !$memcached_recipe ) {
                    throw new \RuntimeException( sprintf("install %s on %s need you create memcached recipe first", $recipe, $recipe->getClient()  ));
                }
                $memcached_config   = $this->_cloud_manger->getRecipeConfiguration( $memcached_recipe->getRecipeName() ) ;
                $memcached_data    = $memcached_config->getProcessArray( $memcached_recipe, $memcached_recipe->getDataBag(), $debug) ;
                $config['extensions']['memcached']['src_options'] .= ' --with-libmemcached-dir=' . $memcached_data['service']['dir'] ;

            }

            foreach($config['extensions'] as $name => $ext ) {
                if( isset($ext['ini']) ) {
                    $prefix = $name ;
                    if( $name == 'apcu' ) {
                        $prefix = 'apc' ;
                    }

                    $ini    = array() ;
                    $ext_ini = $ext['ini'] ;
                    if( isset($config['ini'][$prefix])  ) {
                        \Dev::mergeNoOverWrite( $ext_ini, $config['ini'][$prefix] ) ;
                        unset($config['ini'][$prefix]) ;
                    }

                    foreach($ext_ini as $key => $value ) {
                        if( is_array($value) ) {
                            foreach($value as $_key => $_value ) {
                                $ini[ $prefix . '.' . $key . '.' . $_key ] = $_value ;
                            }
                        } else {
                            $ini[ $prefix . '.' . $key  ] = $value ;
                        }
                    }
                    $config['extensions'][$name]['ini'] = $ini ;
                }
            }
        }

        if( isset($config['ini']) ) {
            foreach($config['ini'] as $key => $value ) {
                if( is_array($value) ) {
                    unset($config['ini'][$key]) ;
                    foreach($value as $_key => $_value ) {
                        $config['ini'][ $key . '.' . $_key ] = $_value ;
                    }
                    unset($value) ;
                }
            }
        }
    }
}