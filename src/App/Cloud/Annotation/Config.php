<?php

namespace App\Cloud\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Config {

    /**
     * @var string
     */
    public $name ;
}