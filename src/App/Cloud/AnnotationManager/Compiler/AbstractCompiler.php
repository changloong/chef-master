<?php
namespace App\Cloud\AnnotationManager\Compiler;

use Doctrine\Common\Annotations\Reader;
use App\Cloud\AnnotationManager\Loader\FileLoader;

use Silex\Application ;

abstract class AbstractCompiler {

    /**
     * @var Application
     */
    protected $container ;

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->container[$key];
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function raw($key) {
        return $this->container->raw($key);
    }

    /**
     * @return Application
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * @param Application $container
     * @return $this
     */
    public function setContainer(Application $container) {
        $this->container = $container;
        return $this;
    }

    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $reader;

    /**
     * @var FileLoader
     */
    protected $loader;

    /**
     * @param Reader $reader
     * @param FileLoader $loader
     */
    public function __construct(Reader $reader, FileLoader $loader) {
        $this->reader = $reader;
        $this->loader = $loader;
    }

    /**
     * @param string $path Directory path to compile
     */
    public function compile($path) {
        $self = $this ;
        $this->loader->setCallback(function($class, $file) use($self) {
            $self->compileCallback($class, $file) ;
        });

        $this->loader->load($path);
    }

    public function compileCallback($class, $file) {
        $reflClass = new \ReflectionClass($class);
        //Ignore abstract classes
        if ($reflClass->isAbstract())
            return ;

        $classAnnotations = $this->reader->getClassAnnotations($reflClass);

        $this->compileClass($reflClass, $classAnnotations);

        foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflMethod) {

            $methodAnnotations = $this->reader->getMethodAnnotations($reflMethod);

            $this->compileMethod($reflClass, $reflMethod, $methodAnnotations);
        }
    }

    /**
     * @param FileLoader $loader
     */
    public function setLoader(FileLoader $loader) {
        $this->loader = $loader;
    }

    /**
     * @return FileLoader
     */
    public function getLoader() {
        return $this->loader;
    }

    /**
     * @param Reader $reader
     */
    public function setReader(Reader $reader) {
        $this->reader = $reader;
    }

    /**
     * @return Reader
     */
    public function getReader() {
        return $this->reader;
    }

    abstract public function compileClass(\ReflectionClass $class, array $annotations);
    abstract public function compileMethod(\ReflectionClass $class, \ReflectionMethod $method, array $annotations);
}
