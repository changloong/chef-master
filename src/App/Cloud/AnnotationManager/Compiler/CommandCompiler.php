<?php
namespace App\Cloud\AnnotationManager\Compiler;

use App\ContainerAwareTrait;


class CommandCompiler extends AbstractCompiler {

    /**
     * @var \Knp\Console\Application
     */
    private $console ;

    public function setConsole(\Knp\Console\Application $console) {
        $this->console  = $console ;
    }

    /**
     * @param \ReflectionClass $class
     * @param array $annotations
     */
    public function compileClass(\ReflectionClass $class, array $annotations) {

        $as = array_filter($annotations, function($annotation) {
            return is_object($annotation) && $annotation instanceof \App\Cloud\Annotation\Command ;
        });

        if( empty($as) ) {
            return ;
        }

        if (count($as) > 1) trigger_error('Multiple Route annotations at a class level are not supported', E_USER_ERROR);

        $class_name = $class->getName() ;
        $command    =  new $class_name( $as[0]->name ) ;
        $this->console->add( $command ) ;
    }


    public function compileMethod(\ReflectionClass $class, \ReflectionMethod $method, array $annotations) {

    }

}
