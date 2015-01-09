<?php

namespace App\Cloud\Configuration\Definition\Builder;

class TreeBuilder extends \Symfony\Component\Config\Definition\Builder\TreeBuilder {
    use TraitDefault, TraitBuilder ;

    public function root($name, $type = 'array', \Symfony\Component\Config\Definition\Builder\NodeBuilder $builder = null) {
        $builder = $builder ?: new NodeBuilder() ;
        return $this->root = $builder->node($name, $type)->setParent($this);
    }

} 