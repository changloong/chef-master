<?php

namespace App\Cloud\Configuration\Definition\Builder;


class IntegerNodeDefinition extends \Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition {
    use TraitDefault, TraitNodeDefinition ;

    /**
     * Ensures that the value is smaller than the given reference.
     *
     * @param mixed $max
     *
     * @return NumericNodeDefinition
     *
     * @throws \InvalidArgumentException when the constraint is inconsistent
     */
    public function max($max)
    {
        if( !$this->isDefaultConfigure() ) {
            return parent::max($max) ;
        }
        return $this;
    }

    /**
     * Ensures that the value is bigger than the given reference.
     *
     * @param mixed $min
     *
     * @return NumericNodeDefinition
     *
     * @throws \InvalidArgumentException when the constraint is inconsistent
     */
    public function min($min)
    {
        if( !$this->isDefaultConfigure() ) {
            return parent::min($min) ;
        }
        return $this;
    }
} 