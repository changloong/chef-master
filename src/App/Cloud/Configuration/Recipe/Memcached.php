<?php

namespace App\Cloud\Configuration\Recipe;

/**
 * @App\Cloud\Annotation\Config("memcached")
 */
class Memcached extends \App\Cloud\Configuration\Recipe {

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        self::configMemcachedRootNode($root, $this, array(

        ), true )  ;
    }

    public function getRecipeName(){
        return 'server' ;
    }

    protected function onRecipeModify(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {
        self::whenRecipeModify( $this, $recipe, $config, $debug );
    }

    public static function configInstallNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node, \App\Cloud\Configuration\Recipe $self, array $defaults = array(), $is_default = false){

        $node
            ->appendIf(
                $self
                    ->createNode('src_url')
                    ->defaultValue('http://192.168.10.20/chef/memcached-1.4.20.tar.gz')
                , $is_default
            )
            ->appendIf(
                $self->createNode('src_package')->defaultValue('memcached-1.4.20')
                , $is_default
            )
            ->appendIf(
                $self
                    ->createNode('libmemcached_src_url')
                    ->defaultValue('http://192.168.10.20/chef/libmemcached-1.0.18.tar.gz')
                , $is_default
            )
            ->appendIf(
                $self->createNode('libmemcached_src_package')->defaultValue('libmemcached-1.0.18')
                , $is_default
            )
            ->append(
                $self->createScalarNode('dir', function( $dir ) {
                    if( $dir ) {
                        if( !preg_match('/^(\/[a-z][a-z0-9\_]{1,32})+\/?$/', $dir) ) {
                            throw new \RuntimeException( sprintf('invalid value: %s', $dir));
                        }
                    }
                    return rtrim( $dir, '/') ;
                } )->defaultValue('/opt/local/memcached')
            )
            ->children()
            ->end()
        ;
    }

    public static function configMemcachedRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root, \App\Cloud\Configuration\Recipe $self, array $defaults = array(), $is_default = false ){

        $root
            ->children()
                ->arrayNode( 'service')
                    ->addDefaultsIfNotSet()
                    ->appendWith( function($node)use( $self, $defaults, $is_default){
                        self::configInstallNode($node, $self, $defaults, $is_default ) ;
                    })
                ->end()
            ->end()
        ;
    }

    public static function whenRecipeModify(\App\Cloud\Configuration\Recipe $self, \App\Cloud\Entity\Recipe $recipe, array & $config, $debug ){

    }

    protected function onRecipeProcess(\App\Cloud\Entity\Recipe $recipe, array $config, $debug = false ){
        if( $recipe->getId() ) {
            $access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false );

        }
    }

    protected function onRecipeExecute(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {
        $access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false );

    }
}
