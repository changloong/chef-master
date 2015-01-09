<?php

namespace App\Cloud\Configuration\Definition\Yaml;


class Helper extends NodePathFinder {

    private  $text = array() ;

    public function __construct($object , $text)
    {
        $this->setNode($object) ;
        $this->text[] = $text ;
    }

    public function addText($text) {
        $this->text[] = $text ;
    }

    public function initHelper(array & $help) {
        $help[ $this->getPath() ] = $this->text ;
    }

    public function saveConverter(array & $cache){

    }

    public function executeConverter(array & $cache){

    }


}