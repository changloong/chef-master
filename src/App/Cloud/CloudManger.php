<?php

namespace App\Cloud;

use App\Cloud\Configuration\ConfigurationFactory ;

class CloudManger {

    const ENTITY_ENV_CLASSNAME = 'App\\Cloud\\Entity\\Env' ;
    const ENTITY_CLIENT_CLASSNAME = 'App\\Cloud\\Entity\\Client' ;
    const ENTITY_RECIPE_CLASSNAME = 'App\\Cloud\\Entity\\Recipe' ;

    /**
     * @var \Silex\Application
     */
    private $app ;

    /**
     * @var array<\App\Cloud\Entity\Env>
     */
    private $env ;

    /**
     * @var string
     */
    private $current_env_name ;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    public $access ;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(\Silex\Application $app){
        $this->access   = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor() ;
        $this->app      = $app ;
        if( file_exists($app['knife.env.current_path']) ) {
            $this->current_env_name = file_get_contents( $app['knife.env.current_path'] ) ;
        }
        $this->load() ;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManger(){
        return  $this->app['db.orm.em'] ;
    }

    public function load( $reload =false ) {
        if( null === $this->env  || $reload ) {
            $this->env  = array() ;
            $orm    = $this->getEntityManger() ;
            if( $reload ) {
                $orm->refresh() ;
            }
            $all    = $orm->getRepository( self::ENTITY_ENV_CLASSNAME )->findAll() ;
            foreach($all as $env) {
                $this->env[ $env->getName() ] = $env ;
            }
        }

        if( $this->current_env_name ) {
            if( !$this->hasEnv( $this->current_env_name ) ) {
                $this->current_env_name = null ;
            }
        }
    }

    /**
     * @param string $name
     * @return \App\Cloud\Entity\Env
     */
    public function getEnv( $name ) {
        if( !isset($this->env[$name]) ) {
            throw new \RuntimeException( sprintf(" env `%s` not exits", $name));
        }
        return $this->env[$name] ;
    }

    /**
     * @return array<\App\Cloud\Entity\Env>
     */
    public function getAllEnv(){
        return $this->env ;
    }

    /**
     * @return string
     */
    public function getCurrentEnvName() {
        return $this->current_env_name ;
    }

    /**
     * @param string $name
     */
    public function setCurrentEnvName( $name ) {
        $this->current_env_name = $name ;
        file_put_contents($this->app['knife.env.current_path'], $this->current_env_name) ;
    }

    /**
     * @return \App\Cloud\Entity\Env
     */
    public function getCurrentEnv() {
        $name   = $this->current_env_name ;
        if( !$name || !isset($this->env[$name]) ) {
            throw new \RuntimeException( sprintf("current env `%s` not exits", $name));
        }
        return $this->env[$name] ;
    }

    /**
     * @return bool
     */
    public function hasEnv($name) {
        return isset($this->env[$name]) ;
    }

    /**
     * @param $name
     * @param \App\Cloud\Entity\Env $env
     * @return \App\Cloud\Entity\Client
     */
    public function getClientByName($name, \App\Cloud\Entity\Env $env) {
        $em = $this->getEntityManger() ;
        $client = $em->getRepository( self::ENTITY_CLIENT_CLASSNAME )->findOneBy(array(
            'env'   => $env->getId() ,
            'name'  => $name ,
        )) ;
        return $client ;
    }

    /**
     * @param string $name
     * @return \App\Cloud\Configuration\Recipe
     */
    public function getRecipeConfiguration($name){
        $cf = $this->app['app.cf'] ;
        return $cf->getConfiguration(ConfigurationFactory::TAG_RECIPE, $name);
    }

    /**
     * @param string $name
     * @return \App\Cloud\Configuration\Recipe
     */
    public function getAllRecipeConfiguration(){
        $cf = $this->app['app.cf'] ;
        return $cf->getConfigurationByTag(ConfigurationFactory::TAG_RECIPE);
    }

    public function hasRecipeConfiguration($name){
        $cf = $this->app['app.cf'] ;
        return $cf->hasConfiguration(ConfigurationFactory::TAG_RECIPE, $name);
    }

    /**
     * @param id
     * @return \App\Cloud\Entity\Recipe
     */
    public function getRecipeById($id) {
        $em = $this->getEntityManger() ;
        $recipe = $em->getRepository( self::ENTITY_RECIPE_CLASSNAME )->findOneBy(array(
            'id'   => $id ,
        )) ;
        return $recipe ;
    }

    /**
     * @param $name
     * @param \App\Cloud\Entity\Client $client
     * @return \App\Cloud\Entity\Recipe
     */
    public function getRecipeByName($name, \App\Cloud\Entity\Client $client, $multiple_name = null ) {

        $em = $this->getEntityManger() ;
        $recipe = $em->getRepository( self::ENTITY_RECIPE_CLASSNAME )->findOneBy(array(
            'client'   => $client->getId() ,
            'recipe_name'  => $name ,
            'multiple_name' => $multiple_name ,
        )) ;

        if( !$recipe ) {
            return null ;
        }

        $_instance = $this->getRecipeConfiguration($name) ;

        if( $_instance->isMultipleRecipe() ) {

            if( !$multiple_name && $_instance->getMultipleNodeDefaultValue() ) {
                throw new \Exception( sprintf('%s cant not has null multiple name for %s', $recipe, $_instance->getMultipleNodePath() ) );
            }
        } else {
            if( $multiple_name ) {
                throw new \Exception( sprintf('%s cant not has multiple name `%s` ', $recipe, $multiple_name));
            }
        }

        return $recipe ;
    }


    public function getPropertyValues(array $properties, array $values, $throw = false ) {
        $_access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, $throw );
        $_values    = array() ;
        foreach($properties as $path ) {
             $_values[ $path ] = $_access->getValue($values, $path ) ;
        }
        return $_values ;
    }

    public function getMultipleName(\App\Cloud\Entity\Recipe $recipe) {
        $config = $this->getRecipeConfiguration( $recipe->getRecipeName() ) ;
        if( $config->isMultipleRecipe() ) {
            return $config->getMultipleName($recipe) ;
        }
    }

    public function getRecipesByRecipeProperties(\App\Cloud\Entity\Recipe $recipe, array $properties , $multiple_name = null , $with_public_recipe = false ) {
        if( \Dev::isSimpleArray($properties) ) {
            $properties = $this->getPropertyValues($properties, $recipe->getDataBag() ) ;
        }
        $list   = $this->getRecipesByClientProperties( $recipe->getClient()->getEnv(), $properties, $recipe->getRecipeName(), $multiple_name, $recipe->getClient(), $with_public_recipe ) ;
        if( $recipe->getId() && isset($list[$recipe->getId()]) ) {
            unset( $list[$recipe->getId()] ) ;
        }
        return $list ;
    }

    public function getRecipesByClientProperties(\App\Cloud\Entity\Env $env, array $properties, $recipe_name, $multiple_name = null , \App\Cloud\Entity\Client $client = null, $with_public_recipe = false, $with_parent_childern = true ) {

        $criteria   = array(
            'recipe_name'  => $recipe_name ,
        );

        if( $multiple_name !== false ) {
            $criteria['multiple_name']  = $multiple_name ;
        }

        if( $client ) {
            $criteria['client']  = $client->getId() ;
        } else {
            $criteria['private']  = false ;
        }

        $results    = array() ;


        $em = $this->getEntityManger() ;
        $findby = function($criteria) use($em, & $results, $properties , $env ) {
            $list  = $em->getRepository( self::ENTITY_RECIPE_CLASSNAME )->findBy( $criteria ) ;

            if( $list ) foreach($list as $recipe) {
                if( $recipe->getClient()->getEnv()->getId() !== $env->getId() ) {
                    continue ;
                }
                $data   = $recipe->getDataBag() ;
                $matched    = true ;
                foreach($properties as $path => $value ) {
                    $_value = $this->access->getValue($data, $path) ;

                    if( $_value !== $value ) {
                        $matched    = false ;
                        break ;
                    }
                }
                if( !$matched ) {
                    continue ;
                }
                $results[ $recipe->getId() ] = $recipe ;
            }
        };

        $findby($criteria) ;

        if( $with_public_recipe ) {
            $criteria   = array(
                'recipe_name'  => $recipe_name ,
                'private'   => false ,
            );
            if( $multiple_name !== false ) {
                $criteria['multiple_name']  = $multiple_name ;
            }
            $findby($criteria) ;
        }

        if( $with_parent_childern ) {
            $config = $this->getRecipeConfiguration( $recipe_name ) ;
            $list   = array() ;
            if( $config->isParentRecipe() ) {
                $list   = $this->getRecipesByClientProperties($env, $properties, $config->getChildRecipeName(), false, $client, $with_public_recipe, false );
            } else if( $config->isChildRecipe() ) {
                $list   = $this->getRecipesByClientProperties($env, $properties, $config->getParentRecipeName(), null, $client, $with_public_recipe, false );
            }
            foreach($list as $recipe) {
                $results[ $recipe->getId() ] = $recipe ;
            }
        }

        return $results ;
    }

    public function getSshPublicKeyFileByEnv(\App\Cloud\Entity\Env $env) {
        return sprintf('%s/app/config/keys/env_%s.pub', $this->app['root.dir'] , $env->getName() );
    }

    public function getSshPrivateKeyFileByEnv(\App\Cloud\Entity\Env $env) {
        return sprintf('%s/app/config/keys/env_%s.pem', $this->app['root.dir'] , $env->getName() );
    }

    /**
     * @param Entity\Client $client
     * @param bool $use_password
     * @return \Ssh\Session
     */
    public function getSshByClient(\App\Cloud\Entity\Client $client, $use_password = false ){
        if ( $use_password ) {
            $configuration = new \Ssh\Configuration( $client->getIp(), $client->getPort() );
            $authentication = new \Ssh\Authentication\Password( $client->getUser(), $client->getPassword() ) ;
            $session = new \Ssh\Session($configuration, $authentication);
        } else {

            $configuration = new \Ssh\Configuration( $client->getIp(), $client->getPort() );
            $authentication = new \Ssh\Authentication\PublicKeyFile(
                $client->getUser(),
                $this->getSshPublicKeyFileByEnv( $client->getEnv() ) ,
                $this->getSshPrivateKeyFileByEnv( $client->getEnv() ) ) ;
            $session = new \Ssh\Session($configuration, $authentication);
        }
        return $session ;
    }

    public function setEntityProperties($entity, array $properties){
        foreach($properties as $key => $value){
            $this->access->setValue($entity, $key, $value) ;
        }
    }
} 