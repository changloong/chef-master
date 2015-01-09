<?php

namespace App\Cloud\Configuration\Definition\Yaml;


abstract class NodeFilter extends  NodePathFinder {

    /**
     * @var array
     */
    protected  $options ;

    /**
     * @var \App\Cloud\Configuration\Configuration
     */
    protected  $config ;

    public function __construct(\App\Cloud\Configuration\Configuration $config, $object , array $options )
    {
        $this->config = $config ;
        $this->options = $options ;
        $this->setNode($object) ;
    }

} 