<?php

namespace App\Cloud\Configuration;


class ConfigurationFactory {

    const TAG_RECIPE    = 'recipe' ;

    /**
     * @var \Silex\Application
     */
    private $app ;

    private $config = array() ;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(\Silex\Application $app){
        $this->app  = $app ;
    }

    public function register(\ReflectionClass $class, $name = null ) {

        $instace    = $class->newInstance( $this->app['app.cm'] ) ;

        if( !$name ) {
            $name   = $instace->getName() ;
        }

        if( !$name ) {
            throw new \Exception( sprintf("`%s` missing name", $class->getName()));
        }

        $tag    = $instace->getTag() ;

        if( !$tag ) {
            throw new \Exception( sprintf("`%s` missing tag", $class->getName()));
        }

        if( isset($this->config[$tag][ $name ]) ) {
            $_instance  = $this->config[$tag][ $name ] ;
            throw new \Exception( sprintf("`%s:%s` duplicate(%s,%s)", $tag,$name,  $class->getName(), get_class($_instance) ) );
        }

        $instace->setName( $name ) ;
        $this->config[$tag][ $name ] = $instace ;
    }

    /**
     * @param $tag
     * @param $name
     * @return array
     * @throws \Exception
     */
    public function getConfigurationArray___($tag, $name, array $configs = array(), $default = false , $debug = false ){
        if( !isset($this->config[$tag][$name]) ) {
            throw new \Exception( sprintf("`%s:%s` not exists", $tag, $name));
        }

        $instance   = $this->getConfigurationInstance($tag, $name) ;
        $_last_default_value = $instance->getDefault() ;
        $instance->setDefault( $default ) ;

        $processor = new \Symfony\Component\Config\Definition\Processor() ;

        if( $debug ) {
            try{
                $results = $processor->processConfiguration($instance, array(
                    $instance->getConfigureRootNodeName() => $configs ,
                ));
            } catch (\Exception $e) {
                return $configs ;
            }
            return $results ;
        }

        $results = $processor->processConfiguration($instance, array(
            $instance->getConfigureRootNodeName() => $configs ,
        ));

        $instance->setDefault( $_last_default_value ) ;

        return $results ;
    }

    public function afterCompilerPass(){
        foreach($this->config[ self::TAG_RECIPE ] as $instance) {
            $this->checkRecipeConfiguration($instance) ;
        }
    }

    private function checkRecipeConfiguration(\App\Cloud\Configuration\Recipe $recipe_config) {
        if( $recipe_config->isParentRecipe() ) {
            $child_name = $recipe_config->getChildRecipeName() ;
            if( !$this->hasConfiguration( self::TAG_RECIPE , $child_name) ) {
                throw new \Exception( sprintf('Configuration:Recipe `%s` child `%s` not exists!', $recipe_config->getName(), $child_name));
            }
            $child_config = $this->getConfiguration(self::TAG_RECIPE, $child_name ) ;
            if( ! $child_config->isChildRecipe() ) {
                throw new \Exception( sprintf('Configuration:Recipe `%s` child `%s` is not instance of `%s\\RecipeChild` !', $recipe_config->getName(), $child_name, __NAMESPACE__ ));
            }
            if( $child_config->getParentRecipeName() !== $recipe_config->getName() ) {
                throw new \Exception( sprintf('Configuration:Recipe `%s` child `%s` parent `%s` not match', $recipe_config->getName(), $child_name,  $child_config->getParentRecipeName() ));
            }
        }
        if( $recipe_config->isChildRecipe() ) {
            $parent_name = $recipe_config->getParentRecipeName() ;
            if( !$this->hasConfiguration( self::TAG_RECIPE , $parent_name) ) {
                throw new \Exception( sprintf('Configuration:Recipe `%s` parent `%s` not exists!', $recipe_config->getName(), $parent_name));
            }
            $parent_config = $this->getConfiguration(self::TAG_RECIPE, $parent_name ) ;
            if( ! $parent_config->isParentRecipe() ) {
                throw new \Exception( sprintf('Configuration:Recipe `%s` parent `%s` is not instance of `%s\\RecipeParent`!', $recipe_config->getName(), $parent_name, __NAMESPACE__ ));
            }
            if( $parent_config->getChildRecipeName() !== $recipe_config->getName() ) {
                throw new \Exception( sprintf('Configuration:Recipe `%s` parent `%s` child `%s` not match', $recipe_config->getName(), $parent_name,  $parent_config->getChildRecipeName() ));
            }
        }
        if( $recipe_config->isMultipleRecipe() ) {
            $multible_default_value = $recipe_config->getMultipleNodeDefaultValue() ;
            if( !$multible_default_value ) {
                if( $recipe_config->isChildRecipe() ) {
                    throw new \Exception( sprintf('Configuration:Recipe `%s` with parent `%s` getMultipleNodeDefaultValue() can not return null ', $recipe_config->getName(), $parent_name ));
                }
            }
        }
    }


    /**
     * @param $tag
     * @param $name
     * @return \App\Cloud\Configuration\Configuration
     * @throws \Exception
     */
    public function getConfiguration($tag, $name){
        if( !isset($this->config[$tag][$name]) ) {
            throw new \Exception( sprintf("configuration tag:%s  name:%s not exists", $tag, $name));
        }
        return $this->config[$tag][$name] ;
    }

    /**
     * @return bool
     */
    public function hasConfiguration($tag, $name) {
        return isset( $this->config[$tag][$name] ) ;
    }

    public function getConfigurationByTag( $tag ) {
        if( !isset($this->config[ $tag ] ) ) {
            throw new \Exception( sprintf("configuration tag `%s` not exists", $tag));
        }
        return $this->config[ $tag ] ;
    }

}