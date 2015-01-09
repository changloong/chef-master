<?php

namespace App\Cloud\Configuration\Definition\Yaml;


use Symfony\Component\Yaml\Exception\RuntimeException;

class RecipeFilter extends NodeFilter {

    /**
     * @var \App\Cloud\Configuration\Recipe
     */
    protected  $config ;

    protected $cached_options  = null ;
    protected $help_text       = null ;

    protected function  setNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $node) {
        parent::setNode($node) ;
        $node
            ->validate()
            ->always( function($value){
                return $this->validate($value) ;
            })
            ->end()
        ;
    }


    public function compilePath(array & $cache ) {
        parent::compilePath($cache) ;

        $options    = array() ;
        if( $this->options['nullable'] ) {
            $options[]  = 'null' ;
        }
        if( !isset($this->options['name']) ) {
            throw new \Exception('big error!');
        }
        $with_public = false ;
        if( isset($this->options['public']) ) {
            $with_public = $this->options['public'] ;
        }

        $properties = array() ;
        if( isset($this->options['criteria']) ) {
            $properties = $this->options['criteria'] ;
        }

        $cm = $this->config->getCloudManger() ;
        $entity = $this->config->getProcessEntity() ;

        $list   = $cm->getRecipesByClientProperties( $entity->getClient()->getEnv(), $properties, $this->options['name'], false, $entity->getClient(), $with_public );

        $this->cached_options  = array() ;
        foreach($list as $recipe) {
            $full_name  =  $this->getRefMultipleName($recipe) ;
            $this->cached_options[ $full_name ] = $recipe ;
            $options[]  = $full_name ;
        }

        if( empty($options) ) {
            $error  = sprintf('%s need recipe:%s, but not created yet!', $this->getPath() , $this->config->getName() ) ;
            throw new \RuntimeException($error);
        }
        $this->help_text = sprintf('recipe:%s : ( %s )', $this->config->getName(),  join(', ', $options ) ) ;
    }

    public function initHelper(array & $help ) {
        $help[ $this->getPath() ] = array( $this->help_text ) ;
    }

    public function saveConverter(array & $cache ) {
        $cache[ $this->getPath() ] = function($value){
            if( !$value ) {
                if( !$this->options['nullable'] ) {
                    throw new \RuntimeException('big error');
                }
                return null ;
            }
            if( !isset($this->cached_options[ $value ]) ) {
                throw new \RuntimeException('big error');
            }
            return $this->cached_options[ $value ]->getId() ;
        } ;
    }

    public function executeConverter(array & $cache ) {
        $cache[ $this->getPath() ] = function($value){

            if( !$value ) {
                if( !$this->options['nullable'] ) {
                    throw new \RuntimeException('big error');
                }
                return null ;
            }

            $recipe = $this->config->getProcessEntity() ;
            $cm = $this->config->getCloudManger() ;
            $_recipe = $cm->getRecipeById( $value )  ;

            if( !$_recipe ) {
                throw new \RuntimeException( sprintf("%s `%s` use recipe(id=%s) not exists!", $recipe, $this->getPath(), $value ));
            }

            /**
             * @todo add  properties match check here
             */

            $_access    = new \Symfony\Component\PropertyAccess\PropertyAccessor(false, true );

            $_data_bag = $_recipe->getDataBag() ;

            $map    = array(
                'recipe_id' => $_recipe->getId() ,
                'recipe_name' => $this->options['name'] ,
                'recipe_multiple_name' => $this->getRefMultipleName($_recipe) ,
            );
            if( isset($this->options['ref']) ) {
                    foreach($this->options['ref'] as $key => $path) {
                        $map[ $key ] = $_access->getValue($_data_bag, $path ) ;
                    }
            }
            return $map ;
        } ;
    }

    protected function getRefMultipleName(\App\Cloud\Entity\Recipe $_recipe ){
        $cm = $this->config->getCloudManger() ;
        $_config    = $cm->getRecipeConfiguration( $_recipe->getRecipeName() ) ;
        $name   = null ;
        if( $_config->isParentRecipe() ) {
            $_child_config  = $cm->getRecipeConfiguration( $_config->getChildRecipeName() ) ;
            $name = $_child_config->getMultipleName( $_recipe->getDataBag() ) ;
        } else {
            $name = $_recipe->getMultipleName() ;
        }
        if( !$name ) {
            $name   = 'null' ;
        }

        $recipe = $this->config->getProcessEntity() ;

        if( $_recipe->getClient()->getEnv()->getId() !==  $recipe->getClient()->getEnv()->getId() ) {
            throw new \ErrorException('big error');
        }

        if( $_recipe->getClient()->getId() !==  $recipe->getClient()->getId() ) {
            $name   = $_recipe->getClient()->getName() . ':' . $name ;
        }

        return $name ;
    }

    public function validate($value) {

        if( $this->config->isExecuteRunning() ) {
            return $value ;
        }

        if( $this->config->isDefault() ) {
            if( $value ) {
                $cm = $this->config->getCloudManger() ;
                $_recipe = $cm->getRecipeById( $value )  ;
                if( !$_recipe ) {
                    return null ;
                }
                return $this->getRefMultipleName($_recipe) ;
            }
            return $value ;
        }

        if( empty($this->cached_options) ) {
            throw new \ErrorException('big error!');
        }

        $options    = join(', ',  array_keys($this->cached_options) )  ;
        if( $this->options['nullable'] ) {
            $options    = 'null, ' . $options ;
        }

        if( !$this->options['nullable'] ) {
            if( !$value ) {
                throw new \InvalidArgumentException(sprintf('%s can not set to %s, available: (%s)  ', $this->getPath(), json_encode($value), $options ));
            }
        }

        if( $value && !isset($this->cached_options[$value]) ) {
            throw new \InvalidArgumentException(sprintf('%s can not set to %s, available: (%s)  ', $this->getPath(), json_encode($value), $options ));
        }

        return $value ;
    }

} 