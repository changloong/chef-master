<?php

namespace App\Cloud\Configuration\Definition\Builder;

class ValidationBuilder  extends \Symfony\Component\Config\Definition\Builder\ValidationBuilder {
    use TraitDefault, TraitBuilder ;

    /**
     * Registers a closure to run as normalization or an expression builder to build it if null is provided.
     *
     * @param \Closure $closure
     *
     * @return ExprBuilder|ValidationBuilder
     */
    public function rule(\Closure $closure = null)
    {
        if (null !== $closure) {
            $this->rules[] = $closure;

            return $this;
        }

        return $this->rules[] = new ExprBuilder($this->node);
    }
} 