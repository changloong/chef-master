<?php

namespace App\Cloud\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Route {

    /**
     * @var string
     */
    public $path ;

    /**
     * @var array<string>
     */
    public $methods = array('GET');

    /**
     * @var string
     */
    public $name ;

    /**
     * @var array<string>
     */
    public $requirements ;
}
