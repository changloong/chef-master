<?php

namespace App\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

class AppBaseCommand extends \Knp\Command\Command {

    /**
     * @var InputInterface
     */
    protected $_input ;

    /**
     * @var OutputInterface
     */
    protected $_output ;


    protected $_work_dir    = null ;
    protected $_root_dir    = null ;

    protected function setup(InputInterface $input, OutputInterface $output){
        $this->_input = $input ;
        $this->_output = $output ;

        $app    = $this->getSilexApplication() ;
        $this->_root_dir    =  $app['root.dir']  ;
    }

    protected function chdir($dir) {
        if( $this->_work_dir === $dir ) {
            return ;
        }
        if( ! @chdir($dir) ) {
            throw new \Exception( sprintf("chdir(%s) error: %s ", $dir, \App::last_error() )) ;
        }
        $this->_work_dir = $dir ;
    }


    protected function forceFileMode($file, $mode) {
        $perm = fileperms($file);
        if( $perm !== $mode ) {
            if( !chmod($file, $mode) ) {
                throw new \Exception( sprintf("change file(%s) mode(%s) error: %s", $file, $mode, error_get_last() ) );
            }
        }
    }


    protected function getInputOption($name, $default_value = '' ){

        $input =  $this->_input ;

        if( !$input->hasOption($name) ) {
            throw new \Exception( sprintf('input has no option `%s`', $name));
        }
        $value    = $input->getOption($name) ;

        $rc = new \ReflectionObject($input) ;
        $property   = $rc->getProperty('definition') ;
        $property->setAccessible( true ) ;
        $definition = $property->getValue($input) ;

        $option     = $definition->getOption($name) ;

        if( !$option->isValueOptional() ) {
            return $value ;
        }

        $default    = $option->getDefault() ;
        if( $value !== $default ) {
            return $value ;
        }

        $property   = $rc->getProperty('tokens') ;
        $property->setAccessible( true ) ;
        $tokens = $property->getValue($input) ;

        $_argument   = sprintf('--%s', $name);
        if( in_array($_argument, $tokens) ) {
            return $default_value ;
        }

        $short      = $option->getShortcut() ;
        if( $short ) {
            $_argument   = sprintf('-%s', $short);
            if( in_array($_argument, $tokens) ) {
                return $default_value ;
            }
        }

        return $default ;
    }

    protected function getIgnoreInputArguments(){
        $input =  $this->_input ;

        $rc = new \ReflectionObject($input) ;
        $property   = $rc->getProperty('definition') ;
        $property->setAccessible( true ) ;
        $definition = $property->getValue($input) ;


        $property   = $rc->getProperty('tokens') ;
        $property->setAccessible( true ) ;
        $tokens = $property->getValue($input) ;

        $arguments   = $input->getArguments() ;

        $cmd    = array() ;
        $finished   = false ;
        $matched_options = array() ;
        foreach($tokens as $i => $token) {

            if( !$finished ) {

                $matched = 0 ;

                if( preg_match('/^--(\w+)$/', $token, $_tok) ) {
                    $name = $_tok[1] ;
                    if( !isset($matched_options[$name]) ) {
                        if( $definition->hasOption($name) ) {
                            $option   = $definition->getOption($name) ;
                            if( !$option->isArray() ) {
                                if( $option->isValueRequired() && $option->getDefault() ) {
                                    $matched = __LINE__ ;
                                    $matched_options[$name]  = __LINE__ ;
                                } else if( $option->isValueOptional() ) {
                                    $matched = __LINE__ ;
                                    $matched_options[$name]  = __LINE__ ;
                                } else if( !$option->acceptValue() ) {
                                    $matched = __LINE__ ;
                                    $matched_options[$name]  = __LINE__ ;
                                } else {
                                    $finished   = __LINE__ ;
                                }
                            } else {
                                $finished   = __LINE__ ;
                            }
                        } else {
                            $finished   = __LINE__ ;
                        }
                    } else {
                        $finished   = __LINE__ ;
                    }
                } else if( preg_match('/^--(\w+)\=(.+)$/', $token, $_tok) ) {
                    $name = $_tok[1] ;
                    if( !isset($matched_options[$name]) ) {
                        if( $definition->hasOption($name) ) {
                            $option   = $definition->getOption($name) ;
                            if ( $option->isArray() ) {
                                $matched = __LINE__ ;
                            } else {
                                if( $option->isValueRequired() ) {
                                    $matched = __LINE__ ;
                                    $matched_options[$name]  = __LINE__ ;
                                } else if( $option->isValueOptional() ) {
                                    $matched = __LINE__ ;
                                    $matched_options[$name]  = __LINE__ ;
                                } else {
                                    $finished   = __LINE__ ;
                                }
                            }
                        } else {
                            $finished   = __LINE__ ;
                        }
                    } else {
                        $finished   = __LINE__ ;
                    }
                } else if( preg_match('/^-(\w+)$/', $token, $_tok) ) {
                    $short  = $_tok[1] ;
                    if( $definition->hasShortcut($short) ) {
                        $option =  $definition->getOptionForShortcut($short) ;
                        $name   = $option->getName() ;
                        if( !isset($matched_options[$name]) ) {
                            if( $definition->hasOption($name) ) {
                                $option   = $definition->getOption($name) ;
                                if( !$option->isArray() ) {
                                    if( $option->isValueRequired() && $option->getDefault() ) {
                                        $matched = __LINE__ ;
                                        $matched_options[$name]  = __LINE__ ;
                                    } else if( $option->isValueOptional() ) {
                                        $matched = __LINE__ ;
                                        $matched_options[$name]  = __LINE__ ;
                                    } else if( !$option->acceptValue() ) {
                                        $matched = __LINE__ ;
                                        $matched_options[$name]  = __LINE__ ;
                                    } else {
                                        $finished   = __LINE__ ;
                                    }
                                } else {
                                    $finished   = __LINE__ ;
                                }
                            } else {
                                $finished   = __LINE__ ;
                            }
                        } else {
                            $finished   = __LINE__ ;
                        }
                    } else {
                        $finished   = __LINE__ ;
                    }
                } else if( preg_match('/^-(\w+)\=(.+)$/', $token, $_tok) ) {
                    $short  = $_tok[1] ;
                    if( $definition->hasShortcut($short) ) {
                        $option =  $definition->getOptionForShortcut($short) ;
                        $name   = $option->getName() ;
                        if( !isset($matched_options[$name]) ) {
                            if( $definition->hasOption($name) ) {
                                $option   = $definition->getOption($name) ;
                                if ( $option->isArray() ) {
                                    $matched = __LINE__ ;
                                } else {
                                    if( $option->isValueRequired() ) {
                                        $matched = __LINE__ ;
                                        $matched_options[$name]  = __LINE__ ;
                                    } else if( $option->isValueOptional() ) {
                                        $matched = __LINE__ ;
                                        $matched_options[$name]  = __LINE__ ;
                                    } else {
                                        $finished   = __LINE__ ;
                                    }
                                }
                            } else {
                                $finished   = __LINE__ ;
                            }
                        } else {
                            $finished   = __LINE__ ;
                        }
                    } else {
                        $finished   = __LINE__ ;
                    }
                } else {
                    if( count($arguments) ) {
                        $_argument  = array_shift($arguments) ;
                        if( $_argument === $token ) {
                            $matched = __LINE__ ;
                        } else {
                            $finished   = __LINE__ ;
                        }
                    } else {
                        $finished   = __LINE__ ;
                    }
                }

                if( !$finished ) {
                    // echo '>>>>>: @', $matched, " = ", $token, "\n" ;
                    continue ;
                }
                // echo '>>>>>: !', $finished, " = ", $token, "\n" ;
            }

            if( strpos($token, '~') !== false || strpos($token, '$') !== false ) {
                $cmd[]  = $token ;
            } else {
                $cmd[]  = escapeshellarg( $token ) ;
            }
        }

        return $cmd ;
    }

    protected function isAllowInputSelect(){
        $interaction    = $this->_input->getOption('no-interaction') ;
        return !$interaction ;
    }
} 