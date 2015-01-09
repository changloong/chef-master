<?php

namespace App\Cloud\Configuration\Recipe;

/**
 * @App\Cloud\Annotation\Config("mysql")
 */
class Mysql extends \App\Cloud\Configuration\RecipeParent {

    protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root) {
        self::configMysqlRootNode($root, $this, array(

        ), true )  ;
    }

    public function getRecipeName(){
        return 'server' ;
    }

    public function getChildRecipeName(){
        return 'mysql_slave' ;
    }

    protected function onRecipeModify(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {
        self::whenRecipeModify( $this, $recipe, $config, $debug );
    }

    public static function configMysqlRoleNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node, \App\Cloud\Configuration\Recipe $self, array $defaults = array(), $is_default = false){

        $node
            ->beforeNormalization()
                ->always(function ($v) use($is_default) {
                    if( $v && isset($v['role']) ) {
                        if( $v['role'] === 'default' ) {
                            if( $is_default ) {
                                unset($v['master']) ;
                            } else {
                                unset($v['slave']) ;
                            }
                        }
                    }
                    return $v;
                })
            ->end() ;

        $node
            ->children()
                ->append(
                    $self
                        ->createNode('role')
                        ->defaultValue('default')
                        ->inArray( $is_default ? 'default,master' : 'default,slave' )
                )
            ->end()
        ;
        if( $is_default ) {
            $node
                ->children()
                    ->arrayNode( 'master' )
                        ->addDefaultsIfNotSet()
                        ->append(
                            $self->createIntegerNode('server_id', function( $listen) {

                            } )->defaultValue(0)->setHelp('set value to 0 will auto assign a server id')
                        )
                        ->append(
                            $self
                                ->createNode('user')
                                ->cannotBeEmpty()
                                ->defaultValue('root')
                        )
                        ->append(
                            $self
                                ->createNode('password')
                                ->defaultValue(null)
                        )
                        ->append(
                            $self->createIntegerNode('connect_retry', function( $listen) {

                            } )->defaultValue(60)
                        )
                ->end()
            ;
        } else {
            $node
                ->children()
                    ->arrayNode( 'slave' )
                        ->addDefaultsIfNotSet()
                            ->append(
                               $self->createRecipeNode('master_server', array(
                                    'name'  => 'mysql' ,
                                    'nullable'  => true ,
                                    'public'    => true ,
                                    'criteria' => array(
                                        '[cluster][role]' => 'master' ,
                                    ) ,
                                    'ref'   => array(
                                        'master'    => '[cluster][master]' ,
                                        'port'    => '[mysqld][port]' ,
                                        'ip'    => '[mysqld][bind_address]' ,
                                    ) ,
                                ))->defaultValue(null)
                            )
                            ->append(
                                $self->createIntegerNode('server_id', function( $listen) {

                                } )->defaultValue(0)->setHelp('set value to 0 will auto assign a server id')
                            )
                            ->append(
                                $self->createScalarNode('replicate_do_db', function( $value ) {
                                    return $value ;
                                })->setHelp('set null will replicate all, split with ,')->defaultValue(null)
                            )
                            ->append(
                                $self->createScalarNode('replicate_ignore_table', function($value) {
                                    return $value ;
                                })->setHelp('split with ,')->defaultValue(null)
                            )


                    ->end()
                ->end()
                ;
        }
    }


    public static function configMysqlInstallNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node, \App\Cloud\Configuration\Recipe $self, array $defaults = array(), $is_default = false){

        $node
                ->append(
                    $self
                        ->createScalarNode('name')
                )
                ->appendIf(
                    $self
                        ->createNode('src_url')
                        ->defaultValue('http://192.168.10.20/chef/percona-server-5.6.20-68.0.tar.gz')
                    , $is_default
                )
                ->appendIf(
                    $self->createNode('src_checksum')->defaultValue('5def6d89792caa70448c67cd510e0f3e')
                    , $is_default
                )
                ->appendIf(
                    $self->createNode('src_package')->defaultValue('percona-server-5.6.20-68.0')
                    , $is_default
                )
                ->append(
                        $self->createScalarNode('dir', function( $dir ) {
                            if( $dir ) {
                                if( !preg_match('/^(\/[a-z][a-z0-9\_]{1,32})+\/?$/', $dir) ) {
                                    throw new \RuntimeException( sprintf('invalid value: %s', $dir));
                                }
                            }
                            return rtrim( $dir, '/') ;
                        } )->defaultValue('/opt/local/mysql56')
                )
                ->append(
                    $self
                        ->createScalarPasswordNode('password')
                        ->defaultValue(null)
                        ->cannotBeEmpty()
                        ->setHelp('for root user')
                )
            ->children()
            ->end()
        ;
    }

    public static function configMysqlRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root, \App\Cloud\Configuration\Recipe $self, array $defaults = array(), $is_default = false ){

        $root
                ->children()
                    ->arrayNode( 'service')
                        ->addDefaultsIfNotSet()
                        ->appendWith( function($node)use( $self, $defaults, $is_default){
                            self::configMysqlInstallNode($node, $self, $defaults, $is_default ) ;
                        })
                    ->end()
                ->end()
            ;
        $root
                ->children()
                    ->arrayNode( 'cluster')
                        ->addDefaultsIfNotSet()
                        ->appendWith( function($node)use( $self, $defaults, $is_default){
                            self::configMysqlRoleNode($node, $self, $defaults, $is_default ) ;
                        })
                    ->end()
                ->end()
            ;


        $root
            ->children()
                ->arrayNode( 'mysqld' )
                    ->addDefaultsIfNotSet()
                    ->append(
                        $self->createIntegerNode('port', function( $listen) {

                        } )->defaultValue(3306)
                    )
                    ->append(
                        $self->createScalarIpNode('bind_address')->defaultValue('127.0.0.1')
                    )
                    ->append(
                        $self->createNode('skip_networking', 'boolean')
                            ->defaultValue( false )
                    )
                    ->append(
                        $self
                            ->createNode('user')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('mysql')
                    )
                    ->append(
                        $self
                            ->createNode('group')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('mysql')
                    )
                    ->append(
                        $self
                            ->createNode('character_set_server')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('utf8')
                            ->inArray('utf8 , latin1, gb2312 ')
                    )
                    ->append(
                        $self
                            ->createNode('collation_server')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('utf8_unicode_ci')
                            ->inArray('utf8_unicode_ci, utf8_general_ci , latin1_swedish_ci')
                    )
                    ->appendIf(
                        $self->createScalarNode('binlog_do_db', function( $value ) {
                            return $value ;
                        })->setHelp('split with ,')->defaultValue(null)
                        , $is_default
                    )
                ->end()
            ->end()
        ;
    }

    public static function whenRecipeModify(\App\Cloud\Configuration\Recipe $self, \App\Cloud\Entity\Recipe $recipe, array & $config, $debug ){

        $child  = $self->getChildRecipeConfiguration() ;
        if( !$child || $child->getName() !== 'mysql_slave' ) {
             throw new \ErrorException('big error!');
        }

        $default_name   = $child->getMultipleName( $config ) ;
        if( $self->isParentRecipe() ) {
            if( $default_name !== $child->getMultipleNodeDefaultValue() ) {
                throw new \ErrorException( sprintf("%s %s value %s must set to: %s", $recipe,  $child->getMultipleNodePath(), json_encode($default_name), json_encode($child->getMultipleNodeDefaultValue()) ));
            }
        }
        if( !$default_name ) {
            $index  = 0 ;
            foreach( $recipe->getClient()->getRecipes() as $_recipe ) {
                if( $_recipe->getRecipeName() !== $child->getName() ) {
                    continue ;
                }
                $index++ ;
            }
            $default_name   = sprintf('mysqld%s', $index) ;
            $child->setMultipleName( $config, $default_name ) ;
        }
        $_config    = array(

        );

        /*
        $_config['service']['dir'] =  '/usr/local/webserver/mysqld' ;
        */

        \Dev::mergeNoOverWrite($config, $_config) ;
    }

    protected function onRecipeProcess(\App\Cloud\Entity\Recipe $recipe, array $config, $debug = false ){
        if( $recipe->getId() ) {
            $access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false );
            $role_path  = '[cluster][role]' ;
            $old_role   = $access->getValue($recipe->getDataBag(), $role_path ) ;
            $new_role   = $access->getValue($config, $role_path ) ;
            if( 'master' === $old_role && $new_role !== $old_role ) {
                // find master, children
                $all = $this->_cloud_manger->getRecipesByClientProperties($recipe->getClient()->getEnv(), array(
                    '[cluster][master]' => $recipe->getId() ,
                ), 'mysql_slave', false, $recipe->getClient(), true );
                if( !empty($all) ) {
                    $slaves = array();
                    foreach($all as $slave) {
                        $slaves[]   = sprintf('%s', $slave) ;
                    }
                    $error  = sprintf("<error>can not set %s %s from %s to %s, because %s </error>",  $recipe, $role_path, json_encode($old_role), json_encode($new_role), join(', ', $slaves ) ) ;
                    throw new \RuntimeException($error) ;
                }
            }

        }
    }

    protected function onRecipeExecute(\App\Cloud\Entity\Recipe $recipe, array & $config, $debug = false ) {
        $access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, false );
        $role_path  = '[cluster][role]' ;
        $role_value   = $access->getValue($recipe->getDataBag(), $role_path ) ;
        if( 'master' === $role_value ) {
            $all = $this->_cloud_manger->getRecipesByClientProperties($recipe->getClient()->getEnv(), array(
                '[cluster][slave][master_server]' => $recipe->getId() ,
            ), 'mysql_slave', false, $recipe->getClient(), true );

            $copy   = array(
                'server_id'  => '[cluster][slave][server_id]' ,
                'ip'  => '[mysqld][bind_address]' ,
                'port'  => '[mysqld][port]' ,
            );

            $slaves = array() ;
            $server_id_map  = array() ;
            $master_server_id = $config['cluster']['master']['server_id'] ;

            if( !$master_server_id ) $master_server_id = $recipe->getId() ;
            $server_id_map[  $master_server_id ] = $recipe ;

            foreach($all as $slave) {
                $map    = array(
                    'recipe_id' => $slave->getId() ,
                    'recipe_name' => $slave->getRecipeName() ,
                    'recipe_multiple_name' => $slave->getMultipleName() ,
                );
                $data = $slave->getDataBag() ;
                foreach($copy as $key => $path ) {
                    $map[$key] = $access->getValue($data, $path) ;
                }
                if( !isset($map['ip']) || $map['ip'] === '0.0.0.0' ) {
                    if( $slave->getClient()->getId() === $recipe->getClient()->getId() ) {
                        $map['ip'] = '127.0.0.1' ;
                    } else {
                        $map['ip'] = $slave->getClient()->getIp() ;
                    }
                }
                if( !$map['server_id'] ) {
                    $map['server_id']  = $slave->getId() ;
                }
                $slaves[] = $map ;
                if( isset($server_id_map[ $map['server_id']  ] ) ) {
                    $error = sprintf( '%s, %s share same server id:%s',  $server_id_map[ $map['server_id']  ] , $slave, $map['server_id'] );
                    throw new \RuntimeException($error) ;
                }
                $server_id_map[ $map['server_id']  ] = $slave ;
            }
            $access->setValue($config, '[cluster][master][slaves]', $slaves) ;
        }

        self::setupMysqlPassword($config) ;
    }


    protected function setRecipeExecuteScripts(\App\Cloud\Entity\Recipe $recipe,  array & $scripts, $debug = false ){
        $scripts['recipe_mysql_init_password.sql']    = sprintf("~/.chefsolo/cache/mysql_init_password_%s.sql", $recipe->getId()) ;
    }

    static function setupMysqlPassword(array & $config) {

        $escape = function($text){
            return strtr($text, array(
                "\x00" => '\x00',
                "\n" => '\n',
                "\r" => '\r',
                '\\' => '\\\\',
                "'" => "\'",
                '"' => '\"',
                "\x1a" => '\x1a'
            ));
        };
        $sql    = array() ;
        $sql[]  = "DROP DATABASE IF EXISTS `test`;" ;
        $sql[]  = "USE `mysql`;" ;
        $sql[]  = "DELETE FROM `db` WHERE 1;" ;
        $sql[]  = "DELETE FROM `user` WHERE `User`!='root';" ;
        $sql[]  = "DELETE FROM `user` WHERE `Host`!='localhost' AND `Host`!='127.0.0.1' ;" ;
        $sql[]  = sprintf("UPDATE `user` SET Password=PASSWORD('%s') WHERE User='root' AND Host='localhost';", $escape($config['service']['password']) ) ;
        $sql[]  = sprintf("grant all privileges on *.* to root@localhost identified by '%s';", $escape($config['service']['password']) ) ;
        $sql[]  = sprintf("grant all privileges on *.* to root@127.0.0.1 identified by '%s';", $escape($config['service']['password']) ) ;
        $sql[]  = "flush privileges;" ;
        $config['service']['password'] = join("\n", $sql) ;
    }
}