<?php

namespace App\Cloud\Configuration\Definition\Builder;


trait TraitDefault {

    /**
     * @var \App\Cloud\Configuration\Configuration
     */
    protected $_instance ;

    /**
     * @param bool $default
     */
    public function setConfigurationInstance(\App\Cloud\Configuration\Configuration $instance) {
        $this->_instance = $instance ;
    }

    /**
     * @return \App\Cloud\Configuration\Configuration
     * @throws \Exception
     */
    public function getConfigurationInstance() {
        if( null === $this->_instance ) {
            if( $this->parent ) {
                $this->_instance = $this->parent->getConfigurationInstance() ;
            }
            if( null === $this->_instance ) {
                throw new \Exception("default value is not setup yet");
            }
        }
        return $this->_instance ;
    }

    /**
     * @return bool
     */
    public function isDefaultConfigure() {
        return $this->getConfigurationInstance()->isDefault() ;
    }

} 