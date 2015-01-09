<?php

namespace App\Cloud\Configuration;


abstract class RecipeChild extends  RecipeMultiple {

    abstract public function getParentRecipeName() ;


    final public function getParentRecipeEntity(\App\Cloud\Entity\Recipe $recipe = null){
        if( null === $recipe ) {
            $recipe = $this->_process_entity ;
        }
        return $this->_cloud_manger->getRecipeByName( $this->getParentRecipeName(), $recipe->getClient() ) ;
    }

    final public function getCookbookName(){
        $parent = $this->_cloud_manger->getRecipeConfiguration( $this->getParentRecipeName() ) ;
        return $parent->getCookbookName() ;
    }

    final public function getRootNodeName(){
        $parent = $this->_cloud_manger->getRecipeConfiguration( $this->getParentRecipeName() ) ;
        return $parent->getRootNodeName() ;
    }

    public function getRecipeName(){
        return $this->name ;
    }



}