<?php

namespace App\Cloud\Script;


class ScriptManger {

    /**
     * @var \Silex\Application
     */
    private $app ;


    /**
     * @var \Twig_Environment
     */
    private $twig ;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(\Silex\Application $app){
        $this->app  = $app ;
    }

    /**
     * @return \Twig_Environment
     */
    protected function getTwig(){
        if( null === $this->twig ) {
            $this->twig = $this->app['app.twig'] ;

            $this->twig->addFilter( new \Twig_SimpleFilter( 'debug', function($var, $deep = 4){
                return \Dev::format_var($var, $deep) ;
            }, array(
                'is_safe'   => array('all') ,
            ))) ;

            $this->twig->addFunction( new \Twig_SimpleFunction( 'fs_copy', function($from, $to, $mode = "0400" ){
                if( !file_exists($from) ) {
                    throw new \Exception( sprintf('%s not exists', $from));
                }
                $writer  = new \CG\Generator\Writer() ;
                $writer->writeln( sprintf("\n# fs_copy -> %s", $to ));
                $lines  = file($from) ;
                foreach($lines as $i => $line) {
                    $line = preg_replace('/[\r\n]+$/', '', $line) ;
                    $writer->writeln( sprintf("echo %s %s %s", escapeshellarg($line), $i ? '>>' : '>', $to ));
                }

                if( !is_string($mode) ) {
                    $_mode   = sprintf('%4o', $mode) ;
                    throw new \Exception( sprintf("convert %s => %s", $mode, $_mode));
                }
                $writer->writeln( sprintf("chmod %s %s", $mode , $to ));

                return $writer->getContent() ;
            }, array(
                'is_safe'   => array('all') ,
            ))) ;


            $this->twig->addFunction( new \Twig_SimpleFunction( 'fs_write', function($file, $data, $mode = "0400" ){

                $writer  = new \CG\Generator\Writer() ;

                $writer->writeln( sprintf("\n# fs_write -> %s", $file ));

                $lines  = explode("\n", $data ) ;

                foreach($lines as $i => $line) {
                    $line = preg_replace('/[\r\n]+$/', '', $line) ;
                    $writer->writeln( sprintf("echo %s %s %s", escapeshellarg($line), $i ? '>>' : '>', $file ));
                }

                if( !is_string($mode) ) {
                    $_mode   = sprintf('%4o', $mode) ;
                    throw new \Exception( sprintf("convert %s => %s", $mode, $_mode));
                }
                $writer->writeln( sprintf("chmod %s %s", $mode , $file ));
                return $writer->getContent() ;
            }, array(
                'is_safe'   => array('all') ,
            ))) ;



            $this->twig->addFunction( new \Twig_SimpleFunction( 'render', function( $file , array $context ){
                return $this->render( $file,  $context ) ;
            }, array(
                'is_safe'   => array('all') ,
            ))) ;
        }

        return  $this->twig  ;
    }

    public function render($file, array $context ) {
        if( !preg_match('/^[a-z][a-z0-9\-\_]{0,64}[a-z0-9]\.\w{2,4}$/', $file) ) {
            throw new \Exception(sprintf('invalid script name `%s`', $file));
        }

        $twig   = $this->getTwig() ;

        foreach($this->app['knife.script.vars'] as $key => $value ) {
            if( !isset( $context[$key]) ) {
                $context[$key] = $value ;
            }
        }

        return $twig->render( $file , $context ) ;
    }
} 