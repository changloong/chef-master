<?php

namespace App\Cloud\Configuration;

/**
 * @App\Cloud\Annotation\Config("default")
 */
class Env extends Configuration {

    /**
     * @var \App\Cloud\Entity\Env
     */
    protected $_process_entity ;

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        $root
            ->append(
                $this->createScalarNode('name')->cannotBeEmpty()
            )
            ->append(
                $this->createScalarNode('domain', function($domain) {
                    if( !preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $domain) ) {
                        throw new \Exception( sprintf("invalid: %s", $domain));
                    }
                    return $domain ;
                } )
            )
            ->append(
                $this->createScalarNode('subnet', function($subnet) {
                     if( !preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})$/', $subnet) )   {
                         throw new \Exception( sprintf("invalid: %s", $subnet));
                     }
                     return $subnet ;
                })
            )
            ->append(
                $this->createScalarIpNode('dns1')->defaultValue('8.8.8.8')
            )
            ->append(
                $this->createScalarIpNode('dns2')->defaultValue('8.8.4.4')
            )
            ->children()
            ->end()
        ;
    }

    public function getTag() {
        return 'env' ;
    }

    public function getEntityClassName(){
        return 'App\\Cloud\\Entity\\Env' ;
    }

    public function getEntityModifyArray($entity, $debug = false){
        if( $entity instanceof \App\Cloud\Entity\Env ) {
            $config = array(
                'name'  => $entity->getName() ,
                'domain'    => $entity->getDomain() ?: 'example.com' ,
                'subnet'    =>  $entity->getSubnet() ,
            ) ;
            return $this->getProcessArray($entity, $config, $debug ) ;
        } else {
            throw new \Exception( sprintf('expect %s, get `%s`', $this->getEntityClassName(), \Dev::type($entity)));
        }
    }

    public function onEntityProcess($entity, array $config, $debug = false){
        if( $entity instanceof \App\Cloud\Entity\Env ) {
            if( $entity->getId() ) {
                if( $entity->getName() !== $config['name'] ) {
                     if( $this->_cloud_manger->hasEnv($config['name']) ) {
                         $_env   = $this->_cloud_manger->getEnv( $config['name'] ) ;
                         $error  = sprintf("<error>can not change env name `%s` to exists env `%s`</error>",  $entity->getName() ,  $_env->getName() ) ;
                         throw new \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException($error) ;
                     }
                }
            } else {
                if( $this->_cloud_manger->hasEnv( $config['name']) ) {
                    $error = sprintf("<error>env `%s` already exists</error>",  $config['name'] ) ;
                    throw new \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException($error) ;
               }
            }
        } else {
            throw new \Exception( sprintf('expect %s, get `%s`', $this->getEntityClassName(), \Dev::type($entity)));
        }
    }
}