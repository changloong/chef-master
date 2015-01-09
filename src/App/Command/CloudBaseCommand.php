<?php

namespace App\Command ;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

class CloudBaseCommand extends AppBaseCommand {

    /**
     * @return \App\Cloud\CloudManger
     */
    protected function getCloudManger(){
        return $this->getSilexApplication()['app.cm'] ;
    }

    /**
     * @return \App\Cloud\Configuration\ConfigurationFactory
     */
    public function getConfigFactory() {
        return $this->getSilexApplication()['app.cf'] ;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManger(){
        return  $this->getSilexApplication()['db.orm.em'] ;
    }

    /**
     * @return \App\Cloud\Script\ScriptManger
     */
    public function getScriptManger() {
        return  $this->getSilexApplication()['app.script.manager'] ;
    }


    protected function setupCookbooks( $update = null ) {
        if( null === $update ) {
            $update = $this->getInputOption('setup', false );
        }

        if( null === $update ) {
            return ;
        }

        $file  = $this->_root_dir. '/.gitmodules' ;
        $ls     = file($file);

        $site_cookbooks    = array() ;
        $module_name    = null ;
        foreach($ls as $i => $ln) {
            $ln     = trim($ln) ;
            if (empty($ln) || 0 === strpos($ln, '#') || 0 === strpos($ln, ';') || 0 === strpos($ln, '\/')  ) {
                continue;
            }
            if( preg_match('/\[\s*submodule\s+(.+)\s*\]/', $ln, $ms) ) {
                $module_name    = $ms[1] ;
                $module_name   = trim($module_name, '"');
                $module_name   = trim($module_name, '\'');
                $map[$module_name]['line'] = $i ;
                continue ;
            }
            if( !$module_name ) {
                throw new \Exception( sprintf("error in file `%s` line %s : %s, no name ", $file, $i , $ln));
            }
            if( !preg_match('/^(.+?)\s*\=\s*(.+)/', $ln, $ms) ) {
                throw new \Exception( sprintf("error in file `%s` line %s : %s, no name ", $file, $i , $ln));
            }
            $key    = trim($ms[1]) ;
            $value  = trim($ms[2])  ;
            $value  = trim($value, '"');
            $value  = trim($value, '\'');

            $site_cookbooks[$module_name][ $key ] = $value ;
        }

        $this->chdir($this->_root_dir) ;
        foreach($site_cookbooks as $module_name => $o ) {
            if( !isset($o['path']) ) {
                throw new \Exception( sprintf('no path for git submodule %s on line %s', $module_name, $o['line']));
            }
            if( !isset($o['url']) ) {
                throw new \Exception( sprintf('no url for git submodule %s on line %s', $module_name, $o['line']));
            }
            $path   = sprintf('%s/%s', $this->_root_dir, $o['path']) ;
            if( file_exists($path) ) {
                $this->updateCookbook($o['path'], $o['url'], $update ) ;
            } else {
                $this->initCookbook($o['path'], $o['url']) ;
            }
        }
    }

    private  function initCookbook($path, $url)
    {

        $this->chdir($this->_root_dir) ;
        $cmd    = sprintf('git clone %s %s', escapeshellarg($url) , escapeshellarg($path) );
        $this->_output->writeln( sprintf(">>> exec: %s", $cmd) );
        $ret = null ;
        passthru($cmd, $ret) ;
    }

    private function updateCookbook($path, $url, $update )
    {
        if( !$update ) {
            return ;
        }
        $this->chdir( sprintf('%s/%s', $this->_root_dir, $path) ) ;
        $cmd    = sprintf('git pull');
        $this->_output->writeln( sprintf(">>> exec: %s for %s <- %s ", $cmd, $path, $url ) );
        $ret = null ;
        passthru($cmd, $ret) ;
    }


    public function getYamlConfigure($tag, $name, $entity, $debug = null ){

        if( null === $debug ) {
            $debug  = $this->_input->getOption('debug') ;
        }

        $instance  = $this->getConfigFactory()->getConfiguration($tag, $name) ;
        $instance->setDefault(true) ;
        $config = $instance->getEntityModifyArray($entity, $debug) ;
        $instance->setDefault(false) ;
        if( !$config ) {
            $config = array() ;
        }
        $data   = $instance->dumpYamlConfigure( sprintf('%s : %s , ', $this->getName(), $entity ), $config ) ;

        $dir    = $this->getSilexApplication()['cache.path'] . '/vim' ;
        if( !file_exists($dir) ) {
            if( !mkdir($dir, 0755)  ) {
                throw new \Exception( sprintf("mkdir(%s) error", $dir));
            }
        }

        $path   = tempnam( $dir , 'config_') ;
        if( !unlink($path) ) {
            throw new \Exception( sprintf("unlink(%s) error", $path));
        }

        $path   = sprintf('%s.yml', $path);
        file_put_contents($path, $data) ;

        $editor = function() use($instance, $entity, $path, $debug ){
            $ret = null ;
            $cmd    = sprintf('vim %s > `tty`', $path) ;
            system( $cmd , $ret);
            if( 0 !== $ret ) {
                throw new \Exception( sprintf(">>> %s\n>>> return %d", $cmd, $ret));
            }
            $data   = file_get_contents($path) ;
            $config = \Symfony\Component\Yaml\Yaml::parse( $data );
            if( !$config ) {
                $config = array() ;
            }
            try{
                $_config    = $instance->getProcessArray($entity, $config, false) ;
                $instance->onEntityProcess($entity, $_config, false) ;
                return $_config ;
            } catch (\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException $e){
                $this->_output->writeln( sprintf(">>> Error : <error>%s</error>", $e->getMessage())) ;
            } catch (\RuntimeException $e){
                $this->_output->writeln( sprintf(">>> Error : <error>%s</error>", $e->getMessage())) ;
            }
        };

        $helper = $this->getHelper('question');

        $_config    = null ;
        while( true ) {
            $_config = $editor() ;
            if( null !== $_config ) {
                break ;
            }
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion('>>> modify again ? ', true );
            if (!$helper->ask($this->_input, $this->_output, $question)) {
                break ;
            }
        }
        if( !unlink($path) ) {
            throw new \Exception( sprintf("unlink(%s) error", $path));
        }
        if( !$_config ) {
            return null ;
        }
        $instance->onEntitySave($entity, $_config, $debug ) ;
        return true ;
    }

    /**
     * @return \App\Cloud\Entity\Env
     */
    protected function getCurrentEnv( $name = null , $select = null , $use_static_current_env = true ){
        if( null === $select ) {
            $select = $this->isAllowInputSelect() ;
        }

        $cm = $this->getCloudManger() ;

        if( $use_static_current_env ) {
            if( null === $name ) {
                $name = $cm->getCurrentEnvName()  ;
                if( !$name ) {
                    if( !$select ) {
                        $this->_output->writeln( sprintf("<error>current env not setup</error>") );
                        return null ;
                    }
                }
                if( !$cm->hasEnv($name)) {
                    $this->_output->writeln( sprintf("<error>current env `%s` not exists</error>", $name ) );
                    if( !$select ) {
                        return null ;
                    }
                    $name   = null ;
                }
            }
        }

        if( !$name ) {
            if( !$select ) {
                return null ;
            }
            return $this->selectEnv() ;
        }

        if( !$cm->hasEnv($name)) {
            if( $name ) {
                $this->_output->writeln( sprintf("<error>env `%s` not exists</error>", $name ) );
            }
            if( $select ) {
                return $this->selectEnv() ;
            } else {
                return null ;
            }
        }

        $env = $cm->getEnv( $name ) ;
        return $env  ;
    }

    /**
     * @return \App\Cloud\Entity\Env
     */
    private function selectEnv(){
        $cm = $this->getCloudManger() ;
        $all = $cm->getAllEnv() ;
        if( empty($all) ) {
            $this->_output->writeln( sprintf("<error>please create at least one env</error>") );
            return null ;
        }
        $this->_output->writeln( sprintf(">>>: please select env by input number:" ) );

        $dialog = $this->getHelper('dialog') ;

        $_names  = $_maps = array_keys($all) ;

        $default_name  = $cm->getCurrentEnvName() ;
        $default_index  = 0 ;
        foreach($_names as $i => $name) {
            if( $name === $default_name ) {
                $default_index = 0 ;
            }
        }

        $_names[ $default_index ] = $_maps[ $default_index ]  . ' * '  ;

        $index = $dialog->select( $this->_output, sprintf('Please select env by input number, default: %s', $_maps[$default_index] ), $_names , $default_index );
        if( !isset($_maps[ $index ]) ) {
            throw new \Exception("big error!");
        }
        $name  = $_maps[ $index ] ;
        return $all[ $name ] ;
    }

    /**
     * @param string $name
     * @return \App\Cloud\Entity\Client
     */
    protected function getCurrentClientByName($name, $select = null ) {
        if( null === $select ) {
            $select = $this->isAllowInputSelect() ;
        }
        $env    = $this->getCurrentEnv(null, $select) ;
        if( !$env ) {
            return null ;
        }

        if( !$name ) {
            if( !$select ) {
                return null ;
            }
            return $this->selectClient($env) ;
        }

        $cm = $this->getCloudManger() ;
        $client = $cm->getClientByName($name, $env) ;
        if( !$client ) {
            if( $name ) {
                $this->_output->writeln( sprintf("client %s for env `%s` not exists" , json_encode($name), $env->getName() ));
            }
            if( !$select ) {
                return null ;
            }
            return $this->selectClient($env) ;
        }
        return $client ;
    }

    /**
     * @return \App\Cloud\Entity\Client
     */
    private function selectClient(\App\Cloud\Entity\Env $env){
        $clients    = $env->getClients() ;
        if( $clients->isEmpty() ) {
            $this->_output->writeln( sprintf("no clients for %s" , $env));
            return null ;
        }

        $map    = array() ;
        $options    = array() ;
        foreach($clients as $client) {
            $id = $client->getId() ;
            $map[ $id ] = $client ;
            $options[ $id ] = sprintf('%s, ip:%s',  $client->getName(), $client->getIp() ) ;
        }

        $dialog = $this->getHelper('dialog');
        $index = $dialog->select( $this->_output, sprintf('Please select client for %s by input number', $env ), $options );

        if( !isset($map[$index]) ) {
            throw new \Exception('error') ;
        }
        return $map[$index] ;
    }

    /**
     * @param string $recipe_name
     * @param string $client_name
     * @return \App\Cloud\Entity\Recipe
     */
    protected function getCurrentRecipeByName($client_name, $recipe_name, $instance_name , $select = null , $create = false ) {
        if( null === $select ) {
            $select = $this->isAllowInputSelect() ;
        }
        $client = $this->getCurrentClientByName($client_name, $select ) ;
        if( !$client ) {
            return null ;
        }

        if( ! $this->getCloudManger()->hasRecipeConfiguration($recipe_name) ) {
            if( $recipe_name ) {
                $this->_output->writeln( sprintf("recipe `%s` not exists", json_encode($recipe_name) ));
                $recipe_name    = null ;
                $instance_name  = null ;
            }
            if( !$select ) {
                return null ;
            }
        } else {
            $_instance  = $this->getCloudManger()->getRecipeConfiguration($recipe_name) ;
            if( !$_instance->isMultipleRecipe() ) {
                if( $instance_name ) {
                    $this->_output->writeln( sprintf("<error>recipe %s for %s can not has name `%s` </error>",   $recipe_name, $client,  $instance_name ) );
                    return null ;
                }
            }
        }


        $recipe = null ;
        if( $recipe_name ) {
            $cm     = $this->getCloudManger() ;
            $recipe =  $cm->getRecipeByName($recipe_name, $client, $instance_name ) ;
            if( !$recipe ) {
                if( !$create ) {
                    if( $instance_name ) {
                        $this->_output->writeln( sprintf("<error>recipe %s(%s) for %s not exists</error>",   $recipe_name, $instance_name, $client ) );
                    } else {
                        $this->_output->writeln( sprintf("<error>recipe %s for %s not exists</error>",   $recipe_name, $client ) );
                    }
                }
            }
        }

        if( !$recipe ) {
            if( !$select ) {
                return null ;
            }
            return $this->selectRecipe($client, $recipe_name, $instance_name, $create ) ;
        }

        return $recipe ;
    }



    /**
     * @return \App\Cloud\Entity\Recipe
     */
    private function selectRecipe(\App\Cloud\Entity\Client $client, $recipe_name,  $instance_name, $create) {

        $cm     = $this->getCloudManger() ;
        if( $recipe_name && !$cm->hasRecipeConfiguration($recipe_name) ) {
            throw new \Exception(sprintf("invalid recipe %s", $recipe_name));
        }

        $map =  $cm->getAllRecipeConfiguration() ;
        $all_names  = join(' ,' , array_keys($map)) ;
        $exists_single_recipes = array() ;
        $exists_entity = array() ;
        $exists_instance = array() ;
        foreach($client->getRecipes() as $_entity) {
            $name = $_entity->getRecipeName() ;
            $_instance = $cm->getRecipeConfiguration( $name ) ;

            if( $recipe_name === $name ) {
                $exists_instance[ $_entity->getId() ]  = $_entity ;
            }

            if( !$_instance->isMultipleRecipe() ) {
                $exists_single_recipes[ $name ] = $_entity ;
            }
            $exists_entity[ $_entity->getId() ] = $_entity ;
        }
        $options = array() ;

        if( $create ) {
            foreach($map as $name => $recipe) {
                if( isset($exists_single_recipes[ $name ]) ) {
                    continue ;
                }
                $options[] = $name ;
            }
            if( empty($options) ) {
                $this->_output->writeln( sprintf("<error>create recipe for %s not finished, all recipes(%s) already created </error>", $client, $all_names) );
                return null ;
            }
        } else {
            if( $recipe_name ) {
                foreach($exists_instance as $_entity ) {
                    $options[ $_entity->getId() ] = sprintf('%s (%s) ', $_entity->getRecipeName() , $_entity->getMultipleName() ) ;
                }
            } else {
                foreach($exists_entity as $_entity ) {
                    $name   = $_entity->getRecipeName() ;
                    $_instance = $cm->getRecipeConfiguration( $name ) ;
                    if( !$_instance->isMultipleRecipe() ) {
                        $options[ $_entity ->getId() ] = $name ;
                    } else {
                        $options[ $_entity ->getId() ] = sprintf('%s (%s) ', $name, $_entity->getMultipleName() ) ;
                    }
                }
            }
            if( empty($options) ) {
                $this->_output->writeln( sprintf("<error>no recipe created for %s </error>", $client, $all_names) );
                return null ;
            }
        }

        $dialog = $this->getHelper('dialog');

        if( $create ) {

            if( !$recipe_name ) {
                $index = $dialog->select( $this->_output, sprintf('Please select recipe for %s by input number', $client ), $options );

                if( !isset($options[$index]) ) {
                    throw new \Exception('error') ;
                }

                $recipe_name = $options[$index] ;
            }

            $recipe  = new \App\Cloud\Entity\Recipe();
            $recipe->setRecipeName($recipe_name) ;
            $recipe->setClient($client) ;

            $instance   = $instance = $cm->getRecipeConfiguration( $recipe_name ) ;
            if( $instance->isMultipleRecipe() ) {
                if( !$instance_name ) while( true ) {
                    $prompt = sprintf('please input `%s`: ', $instance->getMultipleNodePath() ) ;
                    $instance_name = trim( $dialog->ask( $this->_output, $prompt ) ) ;
                    $instance_name = rtrim($instance_name,';') ;
                    $instance_name = rtrim($instance_name) ;
                    if( empty($instance_name) && !$instance->getMultipleNodeDefaultValue() ) {
                        $instance_name = null ;
                        break ;
                    }
                    if( preg_match('/^[a-z][a-z0-9\-\_]{0,64}[a-z0-9]$/', $instance_name) ) {
                        break ;
                    }
                    $this->_output->writeln("%s %s: `%s` invalid, please try again!", $recipe_name, $instance->getMultipleNodePath(),  $instance_name );
                }
                $recipe->setMultipleName( $instance_name ) ;
            }
            return $recipe ;
        } else {

            $index = $dialog->select( $this->_output, sprintf('Please select recipe for %s by input number', $client ), $options );

            if( !isset($options[$index]) ) {
                throw new \Exception('error') ;
            }

            return $exists_entity[ $index ] ;
        }
    }

    protected function runLocalScriptOnClient(\App\Cloud\Entity\Client $client, $name, array $context, $file = null , $return_output = false, $by_password = false ){

        $cm     = $this->getCloudManger() ;
        $asm    = $this->getScriptManger() ;

        $ssh    = $cm->getSshByClient($client, $by_password ) ;
        if( null === $file ) {
            if( !$client->getHome() ) {
                throw new \Exception( sprintf("%s no home", $client )) ;
            }
            $file   = sprintf('%s/knife_%s_%s_%s.sh', $client->getHome(), $name, time(), rand(10000, 99999)) ;
        }
        $context['client']  = $client ;

        $script = $asm->render(sprintf('%s.sh', $name), $context);

        $sftp = $ssh->getSftp() ;
        $sftp->write($file, $script) ;
        if( !$return_output ) {
            if( !$client->getBootstrap() ) {
                throw new \Exception( sprintf("%s not bootstrap yet!", $client )) ;
            }
            return $this->runRemoteScriptOnClient($client, $file) ;
        }
        $ret    = null ;
        try{
            $cmd    = sprintf('bash %s', escapeshellarg($file) ) ;
            $exec   = $ssh->getExec() ;
            $out    = $exec->run( $cmd , $ret ) ;
            if( $ret ) {
                throw new \Exception( sprintf(">>> exec: %s\n>>> return: %s\n>>> output: %s\n", $cmd, json_encode($ret), $out));
            }
            return $out ;
        } catch (\Exception $e){
            throw new \Exception( sprintf(">>> exec: %s\n>>> return: %s\n>>> error: %s\n", $cmd, json_encode($ret), $e->getMessage()), 0, $e );
        }
    }

    protected function runRemoteScriptOnClient(\App\Cloud\Entity\Client $client, $file ){
        $cmd    = sprintf('ssh -i %s -p%s %s@%s bash %s',
            $this->getCloudManger()->getSshPrivateKeyFileByEnv( $client->getEnv() ) ,
            $client->getPort(),
            $client->getUser(),
            $client->getIp(), $file );
        $ret = null ;
        passthru($cmd, $ret) ;
        return $ret ;
    }

    protected function rsyncClientCookbook(\App\Cloud\Entity\Client $client) {
        $cm = $this->getCloudManger() ;
        $rsync  = function($local_dir, $remote_dir) use ($client, $cm) {
            $remote_url = sprintf('%s@%s:%s', $client->getUser(), $client->getIp() , $remote_dir) ;
            $ssh    = sprintf('ssh -p%s -i %s', $client->getPort(),  $cm->getSshPrivateKeyFileByEnv( $client->getEnv() )  );
            $cmd    = sprintf(
                "rsync -avzr --progress --delete --exclude='.git*' --rsh=%s %s %s",
                escapeshellarg($ssh) ,
                escapeshellarg($local_dir) ,
                escapeshellarg($remote_url)
            );

            $this->_output->writeln( sprintf(">>> rsync: %s ", $remote_dir ) );
            $ret = null ;
            passthru($cmd, $ret) ;
        } ;

        $app    = $this->getSilexApplication();
        $root_dir   = $app['root.dir'] ;

        $rsync( sprintf('%s/chef/', $root_dir), '~/.chefsolo/cookbooks' ) ;
    }
}

