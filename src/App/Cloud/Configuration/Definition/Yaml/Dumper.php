<?php

namespace App\Cloud\Configuration\Definition\Yaml;

use \Symfony\Component\Yaml\Inline;

class Dumper extends \Symfony\Component\Yaml\Dumper {

    /**
     * The amount of spaces to use for indentation of nested nodes.
     *
     * @var int
     */
    protected $indentation = 4;

    /**
     * Sets the indentation.
     *
     * @param int     $num The amount of spaces to use for indentation of nested nodes.
     */
    public function setIndentation($num)
    {
        $this->indentation = (int) $num;
    }

    /**
     * Dumps a PHP value to YAML.
     *
     * @param mixed   $input                  The PHP value
     * @param int     $inline                 The level where you switch to inline YAML
     * @param int     $indent                 The level of indentation (used internally)
     * @param bool    $exceptionOnInvalidType true if an exception must be thrown on invalid types (a PHP resource or object), false otherwise
     * @param bool    $objectSupport          true if object support is enabled, false otherwise
     *
     * @return string  The YAML representation of the PHP value
     */
    public function dumpNice($input, $inline = 0, $indent = 0, $exceptionOnInvalidType = false, $objectSupport = false, \CG\Generator\Writer $writer, array & $help , $path = '' )
    {

        $prefix = $indent ? str_repeat(' ', $indent) : '';

        if ($inline <= 0 || !is_array($input) || empty($input)) {
            $writer->write($prefix) ;
            $writer->write( Inline::dump($input, $exceptionOnInvalidType, $objectSupport) ) ;
        } else {
            $isAHash = array_keys($input) !== range(0, count($input) - 1);

            foreach ($input as $key => $value) {
                $willBeInlined = $inline - 1 <= 0 || !is_array($value) || empty($value);

                $_path  = $path . '[' . $key  . ']' ;
                if( $isAHash ) {
                    if( isset($help[$_path]) ) {
                        foreach($help[$_path] as $_help_text ) {
                            $writer->writeln( sprintf("%s# %s -> %s", $prefix, Inline::dump($key, $exceptionOnInvalidType, $objectSupport), preg_replace('/[\r\n]/', ' ', ltrim($_help_text) ) ) ) ;
                        }
                    }
                }

                $writer->write($prefix) ;
                $writer->write( $isAHash ? Inline::dump($key, $exceptionOnInvalidType, $objectSupport) . ':' : '-' ) ;
                $writer->write( $willBeInlined ? ' ' : "\n" ) ;
                $this->dumpNice($value, $inline - 1, $willBeInlined ? 0 : $indent + $this->indentation, $exceptionOnInvalidType, $objectSupport, $writer, $help, $_path ) ;
                $writer->write( $willBeInlined ? "\n" : '' ) ;
            }
        }

    }
} 