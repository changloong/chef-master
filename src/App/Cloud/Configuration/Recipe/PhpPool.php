<?php

namespace App\Cloud\Configuration\Recipe;

/**
 * @App\Cloud\Annotation\Config("php_pool")
 */
class PhpPool extends \App\Cloud\Configuration\RecipeChild {

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        self::configPhpPoolRootNode($root, $this) ;
    }

    public static function configPhpPoolRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root, \App\Cloud\Configuration\Recipe $self, array $defaults = array(), $default_node = false ){
        $root
            ->children()
                ->arrayNode( 'fpm_pool' )
                        ->setHelp('you can set user, group here')
                        ->addDefaultsIfNotSet()
                        ->append(
                            $self
                                ->createScalarNode('name')
                                ->defaultValue( $default_node ?  'www' : null )
                        )
                        ->append(
                            $self->createScalarListenNode('listen')->defaultValue('127.0.0.1:9000')
                        )
                        ->append(
                            $self
                                ->createNode('user')
                        )
                        ->append(
                            $self
                                ->createNode('group')
                         )
                        ->append(
                            $self
                                ->createNode('pm')
                                ->inArray('dynamic,static')
                                ->defaultValue('dynamic')
                        )
                        ->append(
                            $self
                                ->createIntegerNode('max_children')
                                ->defaultValue(5)
                        )
                        ->append(
                            $self
                                ->createIntegerNode('start_servers')
                                ->defaultValue(2)
                        )
                        ->append(
                            $self
                                ->createIntegerNode('min_spare_servers')
                                ->defaultValue(1)
                        )
                        ->append(
                            $self
                                ->createIntegerNode('max_spare_servers')
                                ->defaultValue(3)
                        )
                        ->append(
                            $self
                                ->createNode('process_idle_timeout')
                                ->defaultValue('10s')
                        )
                        ->append(
                            $self
                                ->createIntegerNode('max_requests')
                                ->defaultValue(500)
                        )
                        ->append(
                            $self
                                ->createIntegerNode('request_slowlog_timeout')
                                ->defaultValue(0)
                        )
                        ->append(
                            $self
                                ->createIntegerNode('request_terminate_timeout')
                                ->defaultValue(0)
                        )
                        ->append(
                            $self
                                ->createNode('sendmail_path')
                                ->defaultValue('/usr/sbin/sendmail -t -i -f www@my.domain.com')
                        )
                        ->append(
                            $self
                                ->createNode('display_errors')
                                ->inArray('on,off')
                                ->defaultValue('on')
                        )

                        ->append(
                            $self
                                ->createNode('log_errors')
                                ->inArray('on,off')
                                ->defaultValue('on')
                        )
                        ->append(
                            $self
                                ->createNode('memory_limit')
                                ->defaultValue('32M')
                        )
                        ->append(
                            self::createEnvNode($self)
                        )
                ->end()


        ->end()
        ;
    }

    private static function createEnvNode(\App\Cloud\Configuration\Recipe $self){
         $env = $self->createNode('env', 'array') ;

         $env
             ->requiresAtLeastOneElement()
             ->prototype('scalar')

             ->end()
             ->defaultValue( array(  'PATH' => '/usr/local/bin:/usr/bin:/bin' , 'TMP'  => '/tmp' ))
         ;
         return $env ;
    }

    public function getRecipeName(){
        return 'fpm-pool' ;
    }

    public function getParentRecipeName(){
        return 'php' ;
    }

    public function getMultipleNodePath() {
        return  '[fpm_pool][name]' ;
    }

    public function getMultipleNodeDefaultValue() {
        return  'www' ;
    }

    protected function getUniqueProperties(){
        return array(
            '[fpm_pool][listen]' ,
        );
    }

}
