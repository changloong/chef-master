<?php

namespace App\Cloud\Configuration\Definition\Yaml;


abstract class NodePathFinder {

    /**
     * @var \App\Cloud\Configuration\Definition\Builder\ScalarNodeDefinition
     */
    private  $node ;

    private  $path ;

    final protected function getNode() {
        return $this->node ;
    }

    protected function setNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node) {
        $this->node = $node ;
    }

    private function getCachedPath($object, array & $cache) {
        $id     = spl_object_hash($object) ;
        if( !isset($cache[$id]) ) {
            $rc = new \ReflectionObject( $object ) ;

            $property = $rc->getProperty('name') ;
            $property->setAccessible(true) ;

            $name   = $property->getValue($object) ;

            $property = $rc->getProperty('parent') ;
            $property->setAccessible(true) ;
            $parent = $property->getValue($object) ;

            if( $parent && $parent !== $object  ) {
                $cache[$id] = $this->getCachedPath($parent, $cache) . '[' . $name . ']' ;
            } else {
                // remove the root node
                $cache[$id] = '' ;
            }
        }
        return $cache[$id] ;
    }

    public function compilePath(array & $cache ) {
        $this->path  =$this->getCachedPath( $this->getNode() , $cache) ;
    }

    protected function getPath(){
        return $this->path ;
    }

    abstract public function initHelper(array & $help);
    abstract public function saveConverter(array & $cache);
    abstract public function executeConverter(array & $cache);
} 