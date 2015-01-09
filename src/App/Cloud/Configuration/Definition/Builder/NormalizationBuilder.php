<?php

namespace App\Cloud\Configuration\Definition\Builder;

class NormalizationBuilder  extends \Symfony\Component\Config\Definition\Builder\NormalizationBuilder {
    use TraitDefault, TraitBuilder ;

    /**
     * Registers a closure to run before the normalization or an expression builder to build it if null is provided.
     *
     * @param \Closure $closure
     *
     * @return ExprBuilder|NormalizationBuilder
     */
    public function before(\Closure $closure = null)
    {
        if (null !== $closure) {
            $this->before[] = $closure;

            return $this;
        }

        return $this->before[] = new ExprBuilder($this->node);
    }
} 