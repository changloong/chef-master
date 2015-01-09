<?php

namespace App\Cloud\Configuration ;

/**
 * @App\Cloud\Annotation\Config("default")
 */
class Client extends Configuration {

    /**
     * @var \App\Cloud\Entity\Client
     */
    protected $_process_entity ;

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        $root
            ->append(
                $this->createScalarNode('name')->cannotBeEmpty()->isRequired()
            )
            ->append(
                $this->createScalarIpNode('ip')->cannotBeEmpty()
            )
            ->append(
                $this->createIntegerNode('port', 22 )->max(65534)->min(21)
            )
            ->append(
                $this->createScalarNode('user')->defaultValue('root')->cannotBeEmpty()
            )
            ->append(
                $this->createScalarPasswordNode('password')->defaultValue(null)->cannotBeEmpty()
            )
            ->append(
                $this->createScalarNode('hostname', function($hostname) {

                    return $hostname ;
                } )->defaultValue(null)
            )
            ->children()
                ->arrayNode('roles')
                    ->validate()
                        ->always( function( $roles ){
                            if( !$this->default ){

                            }
                            return $roles ;
                        })
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function getTag() {
        return 'client' ;
    }

    public function getEntityClassName(){
        return 'App\\Cloud\\Entity\\Client' ;
    }

    public function getEntityModifyArray($entity, $debug = false){
        if( $entity instanceof \App\Cloud\Entity\Client ) {
            if( $entity->getId() ) {
                $config = array(
                        'name'  => $entity->getName() ,
                        'ip'    => $entity->getIp() ,
                        'port'    => $entity->getPort() ,
                        'hostname'    => $entity->getHostname() ,
                        'user'  =>  $entity->getUser() ,
                        'password'  => $entity->getPassword() ,
                        'roles' => $entity->getRoles() ,
                   ) ;
            } else {
                $config = array(
                    'name'  => $entity->getName() ,
                    'ip'    => $entity->getEnv()->getDefaultIp() ,
                ) ;
            }
            return $this->getProcessArray($entity, $config, $debug ) ;
        } else {
            throw new \Exception( sprintf('expect %s, get `%s`', $this->getEntityClassName(), \Dev::type($entity)));
        }
    }

    public function onEntityProcess($entity, array $config, $debug = false){
        if( $entity instanceof \App\Cloud\Entity\Client ) {
            if( $entity->getId()  ) {
                if( $config['name'] !== $entity->getName() ) {
                    $_client = $this->_cloud_manger->getClientByName($config['name'], $entity->getEnv() ) ;
                    if( $_client ) {
                        $error = sprintf("can not change client `%s` name into a exists client `%s` in env `%s`" ,
                            $entity->getName(),
                            $config['name'],
                            $entity->getEnv()->getName() ) ;
                        throw new \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException($error) ;
                    }
                }
            } else {
                $_client =  $this->_cloud_manger->getClientByName($config['name'], $entity->getEnv() ) ;
                if( $_client ) {
                    $error = sprintf("<error>client `%s` already exists in env `%s`</error>",
                        $config['name'],
                        $entity->getEnv()->getName() ) ;
                    throw new \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException($error) ;
                }
            }
        } else {
            throw new \Exception( sprintf('expect %s, get `%s`', $this->getEntityClassName(), \Dev::type($entity)));
        }
    }
}