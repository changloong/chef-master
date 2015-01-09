<?php

namespace App\Cloud\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Command {

    /**
     * @var string
     */
    public $name ;

}
