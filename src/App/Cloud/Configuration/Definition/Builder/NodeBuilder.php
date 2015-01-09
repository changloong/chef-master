<?php

namespace App\Cloud\Configuration\Definition\Builder;

use Symfony\Component\Config\Definition\Builder\NodeDefinition ;

class NodeBuilder extends \Symfony\Component\Config\Definition\Builder\NodeBuilder {
    use TraitDefault, TraitBuilder ;

    public function __construct()
    {
        $this->nodeMapping = array(
            'variable'    => __NAMESPACE__.'\\VariableNodeDefinition',
            'scalar'      => __NAMESPACE__.'\\ScalarNodeDefinition',
            'boolean'     => __NAMESPACE__.'\\BooleanNodeDefinition',
            'integer'     => __NAMESPACE__.'\\IntegerNodeDefinition',
            'float'       => __NAMESPACE__.'\\FloatNodeDefinition',
            'array'       => __NAMESPACE__.'\\ArrayNodeDefinition',
            'enum'        => __NAMESPACE__.'\\EnumNodeDefinition',
        );
    }

    public function append(NodeDefinition $node)
    {
        parent::append($node) ;

        $parent = $node->end() ;
        if( $parent ) {
            $node->setConfigurationInstance( $parent->getConfigurationInstance() ) ;
        } else {
            $node->setConfigurationInstance( $this->getConfigurationInstance() ) ;
        }

        return $this;
    }

    public function appendIf( $node, $do_append = true )
    {
        if( $do_append ) {
            $this->append($node) ;
        }
        return $this ;
    }

    public function recipeNode($name, array $options ){

        $node = $this->node($name, 'variable');

        $config = $this->getConfigurationInstance() ;
        if( $config instanceof \App\Cloud\Configuration\Recipe ) {
            $config->addLazyRecipeNode($node, $options) ;
        } else {
            throw new \Exception("can not add recipe to Configuration(%s:%s)", $config->getTag(), $config->getName() ) ;
        }
        return $node ;
    }
} 