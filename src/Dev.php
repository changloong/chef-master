<?php

final class Dev {

    static private $root_dir;
    static private $root_dir_len;

    static public function Init(\Silex\Application $app) {
        
        self::$root_dir = dirname(__DIR__) ;
        self::$root_dir_len = strlen(self::$root_dir);

    }
    
    static public function format_array( array & $args, $use_space = false ) {
        $writer = new \CG\Generator\Writer();
        if( $use_space ) {
            $writer->indent();
        }
        foreach ($args as $i => & $arg) {
            $writer->write("\n$i -> ");
            $writer->indent();
            $visited = array();
            self::export($writer, $visited, $arg, 4);
            $writer->outdent();
        }
        if( $use_space ) {
            $writer->outdent();
        }
        return $writer->getContent() ;
    }

    static public function format_var( $var, $deep = 4 ) {
        $writer = new \CG\Generator\Writer();
        $writer->indent();
        $writer->write("\n");
        $visited = array();
        self::export($writer, $visited, $var, $deep);
        $writer->outdent();
        return $writer->getContent() ;
    }

    static public function debug() {
        $callback = self::get_callback();
        $args   = func_get_args() ;
        echo $callback, self::format_array($args, true) ;
    }

    static public function dump($object, $deep = 5 ) {
        $callback = self::get_callback();
        $output = self::format_var($object, $deep) ;
        echo "\n", $callback, $output ;
    }

    static private function export(\CG\Generator\Writer $writer, array & $visited, $value, $deep = 1, $counter = 0x3ffff) {
        $deep--;
        $counter--;
        if (is_object($value)) {
            if ($value instanceof \DateTime) {
                $writer->write(sprintf('\DateTime(%s, %s)', $value->format("Y/m/d H:i:s"), $value->getTimezone()->getName()));
            } else if ($value instanceof \DateTimeZone) {
                $writer->write(sprintf('\DateTimeZone(%s)', $value->getName()));
            } else if ($value instanceof \Doctrine\ORM\PersistentCollection) {
                $writer->write(sprintf('\Doctrine\ORM\PersistentCollection(%s, %s)', spl_object_hash($value), $value->getTypeClass()->getName()));
            } else if ($value instanceof \Closure) {
                $_rc = new \ReflectionFunction($value);
                $writer->write(sprintf('\Closure(%s, file:%s line:[%d,%s])', spl_object_hash($value), self::fixPath($_rc->getFileName()), $_rc->getStartLine(), $_rc->getEndLine()
                ));
            } else {
                $oid = spl_object_hash($value);
                $object_class = get_class($value);
                if (isset($visited[$oid])) {
                    $writer->write(sprintf("#%s(%s)", $object_class, $oid));
                } else {
                    $visited[$oid] = true;
                    if ($deep > 0) {

                        $skip_properties = array();
                        if ($value instanceof \Doctrine\ORM\Proxy\Proxy) {
                            $skip_properties = array_merge(array(
                                '__initializer__',
                                '__cloner__',
                                '__isInitialized__',
                                    ), $skip_properties);
                        }

                        $writer->write(sprintf("%s(%s) { \n", $object_class, $oid));
                        $writer->indent();
                        $r = new \ReflectionClass($object_class);
                        $output = array();
                        foreach ($r->getProperties() as $p) {
                            if ($p->isStatic()) {
                                continue;
                            }
                            if ($counter < 0) {
                                $writer->writeln("......");
                                break;
                            }
                            $_p = $p->getName();
                            if (in_array($_p, $skip_properties)) {
                                continue;
                            }
                            if ( 0 === strpos($_p, '_') && !$p->isPublic() ) {
                                continue;
                                ;
                            }
                            $p->setAccessible(true);
                            $_value = $p->getValue($value);
                            $writer->write($_p . ' : ');
                            self::export($writer, $visited, $_value, $deep, $counter);
                            $writer->write("\n");
                        }
                        $writer->outdent();
                        $writer->write("}");
                    } else {
                        $r = new \ReflectionClass($object_class);
                        $output = array();
                        foreach ($r->getProperties() as $p) {
                            if (count($output) > 1) {
                                break;
                            }
                            if ($p->isStatic()) {
                                continue;
                            }
                            $p->setAccessible(true);
                            $_value = $p->getValue($value);
                            if (is_object($_value) || is_array($_value)) {
                                continue;
                            }
                            $_p = $p->getName();

                            if ( 0 === strpos($_p, '_') && !$p->isPublic() ) {
                                continue;
                                ;
                            }

                            if (is_string($_value)) {
                                if (strlen($_value) > 0xf) {
                                    $output[$_p] = substr($_value, 0xc) . '..';
                                } else {
                                    $output[$_p] = $_value;
                                }
                            } else {
                                $output[$_p] = $_value;
                            }
                        }

                        $writer->write(sprintf("%s(%s)", $object_class, $oid));
                        if (!empty($output)) {
                            $writer
                                    ->indent()
                                    ->write(" = " . json_encode($output))
                                    ->outdent()
                            ;
                        }
                    }
                }
            }
        } else if (is_array($value)) {
            if ($deep > 0) {
                $writer->writeln("array(");
                $writer->indent();
                foreach ($value as $_key => & $_value) {
                    if ($counter < 0) {
                        $writer->writeln("...");
                        break;
                    }
                    $writer->write($_key . ' => ');
                    self::export($writer, $visited, $_value, $deep, $counter);
                    $writer->write("\n");
                }
                $writer->outdent();
                $writer->writeln(")");
            } else {
                $writer->write(sprintf("array( length = %s ) ", count($value)));
            }
        } else if (null === $value) {
            $writer->write("null");
        } else if (is_string($value)) {
            if (strlen($value) < 0x7f) {
                $writer->write(var_export($value, 1));
            } else {
                $writer->write(var_export(substr($value, 0, 0x7f) . '...', 1));
            }
            $writer->write(sprintf("%d", strlen($value)));
        } else if (is_bool($value)) {
            $writer->write($value ? 'true' : 'false' );
        } else if (is_numeric($value)) {
            $writer->write(var_export($value, 1));
        } else {
            $writer->write(sprintf("%s ( %s ) ", gettype($value), var_export($value, 1)));
        }
    }

    static private function get_callback( $offset = 1 ) {
        $o = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $offset + 3 );
        $file = self::fixPath($o[$offset]['file']);
        $line = $o[ $offset ]['line'];
        $callback = '#' . $file . ":" . $line;

        if (isset($o[ $offset + 1 ])) {
            $fn = null;
            if (isset($o[ $offset + 1 ]['class'])) {
                $fn = $o[ $offset + 1 ]['class'] . $o[ $offset + 1 ]['type'] . $o[ $offset + 1 ]['function'];
            } else if ($o[ $offset + 1 ]['function']) {
                $fn = $o[ $offset + 1 ]['function'];
            }
            if ($fn) {
                $callback .= " @" . $fn;
            }
        }

        $offset++ ;

        if (isset($o[ $offset + 1 ])) {
            $file =  isset($o[$offset]['file']) ? self::fixPath($o[$offset]['file']) : null ;
            $line = isset($o[$offset]['line']) ? $o[$offset]['line'] : null ;
            $callback .= " -> " . $file . ":" . $line;
            $fn = null;
            if (isset($o[ $offset + 1 ]['class'])) {
                $fn = $o[ $offset + 1  ]['class'] . $o[ $offset + 1  ]['type'] . $o[ $offset + 1 ]['function'];
            } else if ($o[ $offset + 1  ]['function']) {
                $fn = $o[ $offset + 1  ]['function'];
            }
            if ($fn) {
                $callback .= " @" . $fn;
            }
        }
        return $callback;
    }

    static private function fixPath($path) {
        $_root_dir = substr($path, 0, self:: $root_dir_len);
        if (self::$root_dir !== $_root_dir) {
            return $path;
        }
        $_path = substr($path, self:: $root_dir_len + 1);
        return $_path;
    }

    static public function isSimpleArray(array & $array) {
        $keys = array_keys($array);
        foreach ($keys as $i => $I) {
            if ($i !== $I) {
                return false;
            }
        }
        return true;
    }

    static public function write_file($path, $content) {
        $need_flush = true;
        if (file_exists($path)) {
            $_content = file_get_contents($path);
            if ($_content === $content) {
                $need_flush = false;
            }
        }
        if ($need_flush) {
            if (false === @file_put_contents($path, $content)) {
                throw new \RuntimeException('Unable to write file ' . $path);
            }
        }
    }

    static public function type($var) {
        if (is_object($var)) {
            return get_class($var);
        }
        return gettype($var);
    }

    static public function merge(array & $a, array & $b) {
        foreach ($b as $key => & $value) {
            if (isset($a[$key])) {
                if (is_array($a[$key]) && is_array($value)) {
                    if (!self::isSimpleArray($a[$key]) || !self::isSimpleArray($b[$key])) {
                        self::merge($a[$key], $value);
                    } else {
                        foreach ($value as $_key => $_value) {
                            $a[$key][] = $_value;
                        }
                    }
                    continue;
                }
            }
            $a[$key] = $value;
        }
    }

    static public function mergeNoOverWrite(array & $a, array & $b, $array_overwrite_scalar = false ) {
        foreach ($b as $key => $value) {
            if( !isset($a[$key]) ) {
                $a[$key] = $value ;
                continue ;
            }
            if( !is_array($a[$key]) ) {
                if( $array_overwrite_scalar && is_array($value) ) {
                    $a[$key] = $value ;
                }
                continue ;
            }
            if( !is_array($value) ) {
                continue ;
            }
            self::mergeNoOverWrite($a[$key], $value);
        }
    }
}