<?php
namespace App\Cloud\AnnotationManager\Compiler;

use Silex\Application ;
use App\ContainerAwareTrait;

use App\Cloud\Annotation\Config as Annot ;

class ConfigurationCompiler extends AbstractCompiler {

    /**
     * @param \ReflectionClass $class
     * @param array $annotations
     */
    public function compileClass(\ReflectionClass $class, array $annotations) {
        $app = $this->getContainer();

        $as = array_filter($annotations, function($annotation) {
            return is_object($annotation) && $annotation instanceof Annot ;
        });

        if (count($as) > 1) trigger_error('Multiple Route annotations at a class level are not supported', E_USER_ERROR);

        if ( empty($as) ) {
            return $as ;
        }

        $app['app.cf']->register($class, $as[0]->name) ;
    }

    /**
     * @param \ReflectionClass $class
     * @param \ReflectionMethod $method
     * @param array $annotations
     */
    public function compileMethod(\ReflectionClass $class, \ReflectionMethod $method, array $annotations) {

    }

}
