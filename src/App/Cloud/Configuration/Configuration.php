<?php

namespace App\Cloud\Configuration ;

use App\Cloud\Configuration\Definition\Builder\NodeBuilder;
use App\Cloud\Configuration\Definition\Builder\TreeBuilder;
use App\Cloud\CloudManger ;
use App\Cloud\Entity\Recipe;

abstract class Configuration implements \Symfony\Component\Config\Definition\ConfigurationInterface {

    /**
     * @var CloudManger
     */
    protected $_cloud_manger ;

    /**
     * @var string
     */
    protected $name ;

    /**
     * @var bool
     */
    protected $default = false ;

    /**
     * @var \App\Cloud\Configuration\Definition\Builder\TreeBuilder
     */
    protected $treeBuilder ;

    protected $_process_entity ;

    /**
     * @var array
     */
    protected $_process_lazy_nodes ;

    /**
     * @var \App\Cloud\Entity\Recipe
     */
    protected $_save_converters = array() ;

    /**
     * @var \App\Cloud\Entity\Recipe
     */
    protected $_execute_converters= array() ;




    final public function __construct(CloudManger $cm) {
        $this->_cloud_manger = $cm ;
    }

    final public function isDefault() {
        return $this->default ;
    }

    final public function addHelp($object, $text){
        $id = spl_object_hash( $object ) ;
        if( isset($this->_process_lazy_nodes[$id] ) ) {
            $this->_process_lazy_nodes[$id]->addText($text) ;
        } else {
            $this->_process_lazy_nodes[$id] = new \App\Cloud\Configuration\Definition\Yaml\Helper($object, $text) ;
        }
    }

    public function getRootNodeName() {
        return sprintf('%s:%s', $this->getTag(), $this->getName() ) ;
    }

    /**
     * @return CloudManger
     */
    final public function getCloudManger(){
        return $this->_cloud_manger ;
    }

    /**
     * @return \App\Cloud\Configuration\Definition\Builder\TreeBuilder
     */
    final public function getConfigTreeBuilder() {

        $treeBuilder = new \App\Cloud\Configuration\Definition\Builder\TreeBuilder() ;
        $treeBuilder->setConfigurationInstance( $this ) ;

        $rootBuilder = new \App\Cloud\Configuration\Definition\Builder\NodeBuilder() ;
        $rootBuilder->setConfigurationInstance( $this ) ;

        $rootNode    = $treeBuilder->root( $this->getRootNodeName(), 'array', $rootBuilder ) ;

        $this->configRootNode($rootNode) ;

        return  $treeBuilder ;
    }

    abstract protected function configRootNode(\Symfony\Component\Config\Definition\Builder\NodeDefinition $root);

    /**
     * @param string $name
     */
    final public function setName( $name ) {
        $this->name = $name ;
    }

    /**
     * @return string
     */
    final public function getName() {
        return $this->name ;
    }

    /**
     * @return bool
     */
    final public function getDefault(){
        return $this->default ;
    }

    final public function setDefault($default){
        $this->default = $default ;
    }

    /**
     * @return string
     */
    abstract public function getTag();


    /**
     * @param string $name
     * @param null $default
     * @return \App\Cloud\Configuration\Definition\Builder\IntegerNodeDefinition
     */
    protected function createIntegerNode( $name , $default = null ){
        $node = $this->createNode( $name, 'integer' ) ;

        if( null !== $default ) {
            $node->defaultValue($default) ;
        }

        return $node;
    }

    /**
     * @param string $name
     * @param callable $callback
     * @param null $error
     * @return \App\Cloud\Configuration\Definition\Builder\ScalarNodeDefinition
     */
    protected function createNode( $name , $type = 'scalar' ){
        $builder = new NodeBuilder() ;
        $builder->setConfigurationInstance( $this ) ;
        $node = $builder->node( $name, $type );
        return $node ;
    }

    /**
     * @param string $name
     * @param callable $callback
     * @param null $error
     * @return \App\Cloud\Configuration\Definition\Builder\ScalarNodeDefinition
     */
    protected function createScalarNode( $name , \Closure $callback = null , $error = null ){
        $node = $this->createNode( $name, 'scalar' );

        if( !$error ) {
            $error  = sprintf('`%s` is invalid', $name ) ;
        }

        if( $callback ) {
            $_callback = function($value) use($callback, $error) {
                if( $this->default ) {
                     try {
                         $value = $callback($value)  ;
                     } catch (\RuntimeException $e) {

                     } catch (\Exception $e){

                     }
                     return $value ;
                }
                return $callback($value)  ;
            } ;
        } else {
            $_callback = function($value) use($callback, $error) {
                if( !preg_match('/^[a-z][a-z0-9\-\_]{0,64}[a-z0-9]$/', $value) )  {
                    if( !$this->default ) {
                        throw new \RuntimeException($error);
                    }
                }
                return $value ;
            } ;
        }

        $node
            ->validate()
                ->always($_callback)
            ->end()
        ;

        return $node;
    }

    /**
     * @param string $name
     * @return \App\Cloud\Configuration\Definition\Builder\ScalarNodeDefinition
     */
    protected function createScalarIpNode( $name ){
        return $this->createScalarNode( $name, function($ip) {
            if( !filter_var( $ip , FILTER_VALIDATE_IP) ) {
                throw new \Exception( sprintf('invalid ip: %s', $ip ));
            }
            return  $ip ;
        } ) ;
    }


    /**
     * @param string $name
     * @return \App\Cloud\Configuration\Definition\Builder\ScalarNodeDefinition
     */
    protected function createScalarPasswordNode( $name ){
        return $this->createScalarNode( $name, function( $value ) {

            return  $value ;
        } ) ;
    }



    /**
     * @param string $name
     * @param callable $callback
     * @param null $error
     * @return \App\Cloud\Configuration\Definition\Builder\ScalarNodeDefinition
     */
    protected function createScalarListenNode( $name ){
        return $this->createScalarNode( $name, function($value) {
            if( preg_match('/^(\d+\.\d+\.\d+\.\d+):(\d+)$/', $value, $ms ) ) {
                if( !filter_var( $ms[1] , FILTER_VALIDATE_IP) ) {
                    throw new \Exception( sprintf('invalid ip: %s', $value ));
                }
                $port = (int) $ms[2] ;
                if( $port < 21 || $port > 65534 ) {
                    throw new \Exception( sprintf('invalid port: %s', $value ));
                }
            } else if (!preg_match('/^(\/[a-z][a-z0-9\_]{1,32})\.sock?$/', $value) ) {
                throw new \RuntimeException( sprintf('invalid value: %s', $value));
            }
            return  $value ;
        } ) ;
    }

    public function onEntitySave($entity, array $config, $debug = false ) {
        $this->_cloud_manger->setEntityProperties($entity, $config) ;
    }

    abstract public function getEntityModifyArray($entity, $debug = false);
    abstract public function onEntityProcess($entity, array $config, $debug = false);
    abstract public function getEntityClassName();

    final public function  dumpYamlConfigure($command, array $array, $inline = 8 , $indent = 4, $exceptionOnInvalidType = false, $objectSupport = false) {

        $help = array() ;
        if( !empty($this->_process_lazy_nodes) ) {
            foreach($this->_process_lazy_nodes as $filter) {
                $filter->initHelper($help) ;
            }
        }

        $writer = new \CG\Generator\Writer() ;

        $multiple_name  = null ;
        if( $this instanceof RecipeMultiple ) {
            $multiple_name   = $this->getMultipleName($array) ;
        }

        if( $multiple_name ) {
            $writer->writeln( sprintf("# %s configuration = %s:%s(%s)", $command, $this->getTag(), $this->getName() , $multiple_name ) ) ;
        } else {
            $writer->writeln( sprintf("# %s configuration = %s:%s", $command, $this->getTag(), $this->getName() ) ) ;
        }

        $yaml  = new \App\Cloud\Configuration\Definition\Yaml\Dumper() ;
        $yaml->setIndentation($indent);
        $yaml->dumpNice($array, $inline, 0, $exceptionOnInvalidType, $objectSupport, $writer , $help );

        return  $writer->getContent();
    }

    final public function getProcessArray($entity, array $config, $debug ) {

        if(  !is_a($entity, $this->getEntityClassName())  ){
            throw new \Exception( sprintf('expect %s, get `%s`', $this->getEntityClassName(), \Dev::type($entity)));
        }

        $this->_process_entity  = $entity ;
        $this->_process_lazy_nodes = array() ;
        $this->_save_converters = array() ;
        $this->_execute_converters = array() ;

        $processor  = new \Symfony\Component\Config\Definition\Processor() ;
        $tree       = $this->getConfigTreeBuilder()->buildTree() ;

        $cache  = array() ;
        if( !empty($this->_process_lazy_nodes) ) {
            foreach($this->_process_lazy_nodes as $filter) {
                $filter->compilePath( $cache ) ;
                $filter->saveConverter( $this->_save_converters ) ;
                $filter->executeConverter( $this->_execute_converters ) ;
            }
        }

        try{
            $results = $processor->process( $tree , array(
                $this->getRootNodeName() => $config ,
            ));
        } catch (\Exception $e) {
            if( $debug ) {
                return $config ;
            }
            throw $e ;
        }

        return $results ;
    }
}