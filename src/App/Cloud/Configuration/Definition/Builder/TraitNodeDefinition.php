<?php

namespace App\Cloud\Configuration\Definition\Builder;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;

trait TraitNodeDefinition {

    /**
     * @param string $text
     * @return $this
     */
    public function setHelp($text) {
        $instance   = $this->getConfigurationInstance() ;
        $instance->addHelp($this, $text);
        return $this;
    }

    public function isRequired()
    {
        if( !$this->isDefaultConfigure() ) {
            $this->required = true ;
        }
        return $this;
    }

    public function defaultValueWhen($value, $eval = true )
    {
        if( $eval ) {
            $this->default = true;
            $this->defaultValue = $value;
        }
        return $this;
    }

    public function defaultValueIfExists($arary, $key )
    {
        if( isset($arary[$key]) ) {
            $this->default = true;
            $this->defaultValue = $arary[$key] ;
        }
        return $this;
    }

    public function inArray($values,  $case = null )
    {
        if( is_string($values) ) {
            $_values = preg_split('/\s*,\s*/' , $values ) ;
        } else {
            $_values = $values ;
            $values = join(', ', $_values );
        }
        if( null !== $case ) {
            foreach($_values as $i => $value ) {
                if( $case ) {
                    $_values[$i] = strtoupper( $value ) ;
                } else {
                    $_values[$i] = strtolower( $value ) ;
                }
            }
        }
        $this->setHelp( sprintf("available values: %s", $values) ) ;

        $this
            ->validate()
                ->ifTrue( function( $value ) use($_values, $case ) {
                    if( !$this->isDefaultConfigure() ) {
                        if( null !== $case ) {
                            $value = $case ?  strtoupper( $value ) : strtolower( $value ) ;
                        }
                        if( !in_array($value, $_values) ){
                            return true ;
                        }
                    }
                })
                ->then(function($value) use($values) {
                     throw new \InvalidArgumentException(sprintf("%s must be one of ( %s )", json_encode($value), $values) ) ;
                  })
            ->end()
        ;

        return $this ;
    }

    public function cannotBeEmpty()
    {
        if( !$this->isDefaultConfigure() ) {
            $this->allowEmptyValue = false;
        }
        return $this;
    }

    /**
     * Gets the builder for validation rules.
     *
     * @return ValidationBuilder
     */
    protected function validation()
    {
        if (null === $this->validation ) {
            $this->validation = new ValidationBuilder($this);
        }

        return $this->validation;
    }

    /**
     * Gets the builder for merging rules.
     *
     * @return MergeBuilder
     */
    protected function merge()
    {
        if (null === $this->merge) {
            $this->merge = new MergeBuilder($this);
        }

        return $this->merge;
    }

    /**
     * Gets the builder for normalization rules.
     *
     * @return NormalizationBuilder
     */
    protected function normalization()
    {
        if (null === $this->normalization) {
            $this->normalization = new NormalizationBuilder($this);
        }

        return $this->normalization;
    }


    public function appendIf( $node, $do_append = true )
    {
        if( $do_append ) {
            $this->append($node) ;
        }
        return $this ;
    }

    public function appendWith(\Closure $callback)
    {
        $callback($this) ;
        return $this;
    }
} 