<?php

namespace App\Cloud\Configuration\Recipe;

/**
 * @App\Cloud\Annotation\Config("nginx")
 */
class Nginx extends \App\Cloud\Configuration\RecipeParent {

    protected function configNginxInstallNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node){

        $node
            ->append(
                $this->createNode('nginx_src_url')->defaultValue('http://192.168.10.20/chef/nginx-1.6.1.tar.gz')
            )
            ->append(
                $this->createNode('nginx_src_package')->defaultValue('nginx-1.6.1')
            )
            ->append(
                $this->createNode('nginx_src_checksum')->defaultValue('5def6d89792caa70448c67cd510e0f3e')
            )
            ->append(
                $this->createNode('prefix_dir')
                    ->defaultValue('/opt/local/nginx')
            )
            ->children()
            ->end()
        ;
    }

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        $root
            ->children()
                ->arrayNode( 'service')
                    ->addDefaultsIfNotSet()
                    ->appendWith( function($node){
                        $this->configNginxInstallNode($node) ;
                    })
                ->end()
            ->end()
        ;

        NginxSite::configNginxSiteRootNode($root, $this, array(
            'default_server'   => true ,
            'root'  => '/opt/web/default/public_html'
        ), true ) ;
    }

    public function getRecipeName(){
        return 'server' ;
    }

    public function getChildRecipeName(){
        return 'nginx_site' ;
    }
}