<?php

namespace App\Cloud\Configuration\Recipe;

/**
 * @App\Cloud\Annotation\Config("nginx_site")
 */
class NginxSite extends \App\Cloud\Configuration\RecipeChild {

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        self::configNginxSiteRootNode($root, $this) ;
    }

    public static function configNginxSiteRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root, \App\Cloud\Configuration\Recipe $self, array $defaults = array() ,  $default_node = false ){

        $root->children()
            ->arrayNode( 'site' )
                ->setHelp('you can set user, group here')
                ->addDefaultsIfNotSet()
                    ->append(
                        $self
                            ->createScalarNode('name')
                            ->defaultValue( $default_node ? 'default' : null )
                    )
                    ->append(
                        $self->createScalarListenNode('listen')->defaultValue('0.0.0.0:80')
                    )
                    ->append(
                        $self
                            ->createNode('default_server', 'boolean')
                            ->defaultValueIfExists( $defaults, 'default_server' )
                    )
                    ->append(
                        $self
                            ->createNode('root')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue( isset($defaults['root']) ? $defaults['root'] : null )
                    )
                    ->append(
                        $self
                            ->createNode('server_name')
                    )
                    ->append(
                        $self
                            ->createNode('autoindex')
                            ->defaultValueIfExists( $defaults, 'autoindex' )
                            ->inArray('on,off')
                    )
                    ->append(
                        self::createPhpNode($self)
                    )
                ->end()
            ->end()
        ;
    }

    public function getParentRecipeName() {
        return 'nginx' ;
    }

    public function getRecipeName(){
        return 'site' ;
    }

    public function getMultipleNodePath() {
        return  '[site][name]' ;
    }

    public function getMultipleNodeDefaultValue() {
        return  'default' ;
    }

    protected function getUniqueProperties(){
        return array(
            '[site][root]' ,
        );
    }

    private static function createPhpNode(\App\Cloud\Configuration\Recipe $self) {

        $node   = $self
            ->createNode('php', 'array') ;

        $node
            ->addDefaultsIfNotSet()
            ->beforeNormalization()
                ->ifTrue(function($v) {
                    // $v contains the raw configuration values
                    return !isset($v['pool']) || empty($v['pool']) ;
                })
                ->then(function($v) {
                    unset($v['path']) ;
                    return $v;
                })
            ->end()
            ->children()
                ->recipeNode('pool', array(
                        'name'  => 'php_pool' ,
                        'nullable'  => true ,
                        'ref'   => array(
                            'listen'    => '[fpm_pool][listen]' ,
                        ),
                    ))
                ->defaultValue(null)
             ->end()

            ->end()
        ;


        return $node ;
    }

}