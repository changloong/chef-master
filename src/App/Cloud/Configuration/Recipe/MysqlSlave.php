<?php

namespace App\Cloud\Configuration\Recipe;

/**
 * @App\Cloud\Annotation\Config("mysql_slave")
 */
class MysqlSlave extends \App\Cloud\Configuration\RecipeChild {

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        Mysql::configMysqlRootNode($root, $this, array(

        ));
    }


    public function getRecipeName(){
        return 'slave' ;
    }

    public function getParentRecipeName() {
        return 'mysql' ;
    }

    public function getMultipleNodePath() {
        return '[service][name]' ;
    }

    public function getMultipleNodeDefaultValue() {
        return  'mysqld' ;
    }

    protected function getUniqueProperties(){
        return array(
            '[service][dir]' ,
            '[mysqld][port]' ,
        );
    }

    protected function onRecipeModify(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {
        Mysql::whenRecipeModify( $this, $recipe, $config, $debug);
    }


    protected function onRecipeExecute(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {
        $access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false );


        $master_entity  = $this->getParentRecipeEntity() ;
        $mastar_basedir_path   = '[service][dir]' ;
        $basedir_value  = $access->getValue($master_entity->getDataBag(), $mastar_basedir_path) ;
        $basedir_path   = '[mysqld][basedir]' ;
        $access->setValue($config, $basedir_path, $basedir_value ) ;

        $role_path  = '[cluster][role]' ;
        $role_value   = $access->getValue($recipe->getDataBag(), $role_path ) ;


        if( 'slave' === $role_value ) {
            $all = $this->_cloud_manger->getRecipesByClientProperties($recipe->getClient()->getEnv(), array(
                '[cluster][slave][master_server]' => $recipe->getId() ,
            ), 'mysql_slave', false, $recipe->getClient(), true );


            $slave_server_id_path    = '[cluster][slave][server_id]' ;
            $master_server_id_path    = '[cluster][slave][server_id]' ;

            $server_id_map  = array() ;
            $this_server_id = $access->getValue($config, $slave_server_id_path) ;
            if( !$this_server_id ) $this_server_id = $recipe->getId() ;
            $server_id_map[  $this_server_id ] = $recipe ;


            foreach($all as $slave) {
                $data = $slave->getData() ;
                if( $slave->getRecipeName() === $this->getName() ) {
                    $server_id = $access->getValue($data,  $slave_server_id_path ) ;
                } else {
                    $server_id = $access->getValue($data,  $master_server_id_path ) ;
                }
                if( !$server_id ) {
                    $server_id = $slave->getId() ;
                }
                if( isset($server_id_map[ $server_id  ] ) ) {
                    $error = sprintf( '%s, %s share same server id:%s',  $server_id_map[ $server_id  ] , $slave, $server_id );
                    throw new \RuntimeException($error) ;
                }
                $server_id_map[ $server_id  ] = $slave ;
            }
        }
        Mysql::setupMysqlPassword($config) ;
    }
}