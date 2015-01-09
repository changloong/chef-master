<?php

namespace App\Cloud\Configuration;


abstract class RecipeParent  extends Recipe {

    abstract public function getChildRecipeName() ;

    final public function getChildMultipleName( array $config ) {
        $child = $this->getChildRecipeConfiguration() ;
        return $this->_cloud_manger->access->getValue($config, $child->getMultipleNodePath() ) ;
    }

    final public function setChildMultipleName( array & $config, $value) {
        $child = $this->getChildRecipeConfiguration() ;
        $this->_cloud_manger->access->setValue($config, $child->getMultipleNodePath(), $value) ;
    }

}
