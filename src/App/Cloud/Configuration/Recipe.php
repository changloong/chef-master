<?php

namespace App\Cloud\Configuration ;

abstract class Recipe extends Configuration {

    /**
     * @var \App\Cloud\Entity\Recipe
     */
    protected $_process_entity ;

    protected $_execute_running ;

    /**
     * @return \App\Cloud\Entity\Recipe
     */
    final public function getProcessEntity(){
        return $this->_process_entity ;
    }


    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        $root
            ->children()
                ->scalarNode('password')->end()
            ->end()
        ;
    }

    final public function getTag() {
        return 'recipe' ;
    }

    public function getEntityClassName(){
        return 'App\\Cloud\\Entity\\Recipe' ;
    }

    public function getCookbookName(){
        return $this->name ;
    }

    public function getRecipeName(){
        return $this->name ;
    }

    public function getRootNodeName() {
        return $this->getCookbookName() ;
    }

    final public function isMultipleRecipe(){
        return $this instanceof RecipeMultiple ;
    }

    final public function isParentRecipe(){
        return $this instanceof RecipeParent ;
    }

    final public function isChildRecipe(){
        return $this instanceof RecipeChild ;
    }

    /**
     * @return RecipeParent
     */
    final public function getParentRecipeConfiguration() {
        if( $this->isParentRecipe() ) {
            return $this ;
        }
        if( $this->isChildRecipe() ) {
            return $this->_cloud_manger->getRecipeConfiguration( $this->getParentRecipeName() ) ;
        }
    }

    /**
     * @return RecipeChild
     */
    final public function getChildRecipeConfiguration() {
        if( $this->isChildRecipe() ) {
            return $this;
        }
        if( $this->isParentRecipe() ) {
            return $this->_cloud_manger->getRecipeConfiguration( $this->getChildRecipeName() ) ;
        }
    }


    final public function isExecuteRunning(){
        return $this->_execute_running ;
    }

    public function isPrivateRecipe(\App\Cloud\Entity\Recipe $recipe){
        return true ;
    }

    final public function getEntityModifyArray($entity, $debug = false ){
        if( !$entity instanceof \App\Cloud\Entity\Recipe  ) {
            throw new \Exception( sprintf('expect %s, get `%s` ', $this->getEntityClassName(), \Dev::type($entity)) );
        }

        if( $this->isChildRecipe() ) {
            $_parent_entity = $this->_cloud_manger->getRecipeByName($this->getParentRecipeName() , $entity->getClient() ) ;
            if( !$_parent_entity ) {
                throw new \Exception( sprintf('`%s` parent recipe not create yet! ', $entity , $this->getParentRecipeName()) );
            }
        }

        $config = $entity->getDataBag() ;

        if( $this->isMultipleRecipe() ) {
            $this->setMultipleName($config, $entity->getMultipleName() ) ;
        }

        if( $this->isParentRecipe() ) {
            $this->setChildMultipleName($config, $this->getChildRecipeConfiguration()->getMultipleNodeDefaultValue() ) ;
        }

        $this->onRecipeModify($entity, $config, $debug) ;

        return $this->getProcessArray($entity, $config, $debug ) ;
    }

    final public function onEntitySave($entity, array $config,  $debug = false ){
        if( $entity instanceof \App\Cloud\Entity\Recipe  ) {

            $this->onRecipeSave($entity, $config, $debug ) ;

            if( $this->isMultipleRecipe() ) {
                $multiple_name  = $this->getMultipleName($config) ;
                $entity->setMultipleName( $multiple_name )  ;
            } else {
                $entity->setMultipleName(null) ;
            }

            if( $this->isChildRecipe() ) {
                $_parent_entity = $this->_cloud_manger->getRecipeByName($this->getParentRecipeName() , $entity->getClient() ) ;
                if( !$_parent_entity ) {
                    throw new \Exception( sprintf('`%s` parent recipe not create yet! ', $entity , $this->getParentRecipeName()) );
                }
                $entity->getParent( $_parent_entity ) ;
            }

            foreach($this->_save_converters as $path => $callback) {
                $this->_cloud_manger->access->setValue($config, $path, $callback($this->_cloud_manger->access->getValue($config, $path)) ) ;
            }

            $entity->setPrivate( $this->isPrivateRecipe($entity) ) ;
            $entity->setDataBag( $config ) ;

        } else {
            throw new \Exception( sprintf('expect %s, get `%s` ', $this->getEntityClassName(), \Dev::type($entity)) );
        }
    }

    final public function onEntityProcess($entity, array $config, $debug = false){
        if( $entity instanceof \App\Cloud\Entity\Recipe ) {

            $recipe_name    = $entity->getRecipeName() ;

            $multiple_default_name  = null ;
            $multiple_name  =  null ;

            $unique = array() ;
            $_unique  = null ;

            if( $this->isMultipleRecipe() ) {
                $unique[]  = $this->getMultipleNodePath() ;
                $multiple_default_name = $this->getMultipleNodeDefaultValue() ;
                $multiple_name  =  $this->getMultipleName($config) ;
                if( !$multiple_name ) {
                    if( $multiple_default_name ) {
                        throw new \RuntimeException( sprintf('code error: %s `%s` can not be %s', $entity, $this->getMultipleNodePath(), json_encode($multiple_name) ));
                    }
                }
                $_unique = $this->getUniqueProperties() ;
            }

            if( $this->isParentRecipe() ) {
                $_child_config  = $this->_cloud_manger->getRecipeConfiguration( $this->getChildRecipeName() ) ;

                $multiple_name = $_child_config->getMultipleName($config) ;

                if( $multiple_name !== $_child_config->getMultipleNodeDefaultValue() ) {
                    $error  = sprintf("<error>recipe %s %s must set to %s, </error>",  $entity, $_child_config->getMultipleNodePath(),
                        json_encode($_child_config->getMultipleNodeDefaultValue() ),
                        json_encode($multiple_name) ) ;
                    throw new \RuntimeException($error) ;
                }

                $unique[]  = $_child_config->getMultipleNodePath() ;
                $_unique   = $_child_config->getUniqueProperties() ;
            }
            if( !empty($_unique) ) {
                foreach($_unique as $path) {
                    if( !in_array($path, $unique) ) {
                        $unique[]   = $path ;
                    }
                }
            }

            $_recipe = $this->_cloud_manger->getRecipeByName( $recipe_name, $entity->getClient(), $multiple_name) ;
            $error  = null ;
            if( $_recipe && $_recipe->getId() !== $entity->getId() ) {
                if( $multiple_name ) {
                    $error  = sprintf("<error>recipe `%s(%s)` already exists in `%s`</error>",  $recipe_name, $multiple_name, $entity->getClient() ) ;
                } else {
                    $error  = sprintf("<error>recipe `%s` already exists in `%s`</error>",  $recipe_name, $entity->getClient() ) ;
                }
            }
            if( $error ) {
                throw new \RuntimeException($error) ;
            }
            foreach($unique as $path) {
                $error  = null ;
                try{
                    $value  = $this->_cloud_manger->getPropertyValues( array($path) , $config , true ) ;
                } catch(\Symfony\Component\PropertyAccess\Exception\NoSuchIndexException $e) {
                    throw $e ;
                }

                try {
                    $list    = $this->_cloud_manger->getRecipesByRecipeProperties($entity, $value, false, $entity->getClient() ) ;
                    if( !empty($list) ) {
                        $_duplicate = array() ;
                        foreach($list as $_entity) {
                            $_duplicate[]   = sprintf('%s', $_entity) ;
                        }
                        $error  = sprintf("<error>recipe %s %s=`%s` already exists in `%s`</error>",  $entity, $path, $value[$path], join(', ', $_duplicate ) ) ;
                    }
                } catch (\Exception $e) {
                    throw new \Exception('big error', 0, $e) ;
                }
                if( $error ) {
                    throw new \RuntimeException($error) ;
                }
            }

        } else {
            throw new \Exception( sprintf('expect %s, get `%s` ', $this->getEntityClassName(), \Dev::type($entity)) );
        }

        $this->onRecipeProcess($entity, $config,  $debug) ;

    }

    final public function getRecipeExecuteArray(\App\Cloud\Entity\Recipe $recipe, $debug = false , $with_run_list = false ){

        $this->_execute_running = true ;

        $config = $recipe->getDataBag() ;
        if( !$config ) {
            $config = array() ;
        }

        $this->setDefault(false) ;
        $root_node_name = $this->getRootNodeName()  ;

        $data_bag   = array() ;

        /**
         * @todo add vars data
         */

        if( $this->isChildRecipe() ) {
            $parent_config      = $this->_cloud_manger->getRecipeConfiguration( $this->getParentRecipeName() ) ;
            $parent_recipe      =  $this->_cloud_manger->getRecipeByName( $parent_config->getName(), $recipe->getClient() ) ;
            $data_bag    = $parent_config->getRecipeExecuteArray($parent_recipe, $debug) ;
        }

        $results = $this->getProcessArray($recipe, $config , $debug);
        $this->onRecipeExecute($recipe, $results, $debug) ;


        $_access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false);
        foreach($this->_execute_converters as $_path => $_callback) {
            $_access->setValue($results, $_path, $_callback($_access->getValue($results, $_path)) ) ;
        }

        $_data_bag  = array (
            $root_node_name => $results ,
        );

        \Dev::merge($data_bag, $_data_bag) ;

        if( $with_run_list ) {
            $data_bag['run_list'][] = sprintf('recipe[%s::%s]', $this->getCookbookName() , $this->getRecipeName() ) ;
            $data_bag['knife_client_id']   = $recipe->getClient()->getId() ;
            $data_bag['knife_client_name']   = $recipe->getClient()->getName() ;
            $data_bag['knife_recipe_id']   = $recipe->getId() ;
            $data_bag['knife_recipe_name']   = $recipe->getRecipeName() ;
            if( $this->isParentRecipe()|| $this->isChildRecipe() ) {
                $data_bag['knife_recipe_multiple_name']   = $this->getChildRecipeConfiguration()->getMultipleName( $recipe->getDataBag() ) ;
            } else if( $this->isMultipleRecipe() ) {
                $data_bag['knife_recipe_multiple_name']   =  $this->getMultipleName( $recipe->getDataBag() ) ;
            } else {
                $data_bag['knife_recipe_multiple_name']   = null ;
            }
            $data_bag['knife_recipe_scripts']   = array() ;
            $this->setRecipeExecuteScripts($recipe, $data_bag['knife_recipe_scripts'], $debug) ;
        }
        return $data_bag ;
    }

    protected function setRecipeExecuteScripts(\App\Cloud\Entity\Recipe $recipe,  array & $scripts, $debug = false ){

    }

    protected function onRecipeProcess(\App\Cloud\Entity\Recipe $recipe, array $config, $debug = false ){

    }

    protected function onRecipeSave(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ){

    }

    protected function onRecipeModify(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {

    }

    protected function onRecipeExecute(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {

    }

    public function addLazyRecipeNode($node, array $options){
        $this->_process_lazy_nodes[] = new \App\Cloud\Configuration\Definition\Yaml\RecipeFilter($this, $node, $options) ;
    }

    protected function createRecipeNode( $name , array $options ){
        $node = $this->createNode( $name, 'variable' ) ;
        $this->addLazyRecipeNode($node, $options) ;
        return $node;
    }
}