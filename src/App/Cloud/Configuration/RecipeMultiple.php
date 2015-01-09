<?php

namespace App\Cloud\Configuration;


abstract class RecipeMultiple extends Recipe {

    abstract public function getMultipleNodePath();

    public function getMultipleNodeDefaultValue(){
        return null ;
    }

    public function getMultipleName( array $config ) {
        return $this->_cloud_manger->access->getValue($config, $this->getMultipleNodePath()  ) ;
    }

    public function setMultipleName( array & $config, $value ) {
        $this->_cloud_manger->access->setValue($config, $this->getMultipleNodePath(), $value) ;
    }

    protected function getUniqueProperties(){
        return array() ;
    }

}