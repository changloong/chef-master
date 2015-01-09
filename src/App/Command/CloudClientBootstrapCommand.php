<?php

namespace App\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @App\Cloud\Annotation\Command()
 */
class CloudClientBootstrapCommand extends CloudBaseCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('client', InputArgument::OPTIONAL, 'client name', null ) ,
                new InputOption('hostname', null , InputOption::VALUE_NONE, 'force set hostname by client configure' ) ,
                new InputOption('bash', null , InputOption::VALUE_NONE, 'force bash path' ) ,
                new InputOption('norsync', null , InputOption::VALUE_NONE, 'skip rsync' ) ,
                new InputOption('setup', null , InputOption::VALUE_OPTIONAL, 'run setup' ) ,
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:client:bootstrap')
            ->setDescription('bootstrap knife client')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setup($input, $output) ;

        $client = $this->getCurrentClientByName( $input->getArgument('client') ) ;
        if( !$client ) {
            return ;
        }

        $cm     = $this->getCloudManger() ;
        $ssh    = $cm->getSshByClient($client , true) ;

        $exec   = $ssh->getExec() ;
        $run    = function($cmd) use($exec, $output){
            $ret    = null ;
            try{
                $out = $exec->run( $cmd , $ret ) ;
                if( $ret ) {
                    throw new \Exception( sprintf(">>> exec: %s\n>>> return: %s\n>>> output: %s\n", $cmd, json_encode($ret), $out));
                }
                return $out ;
            } catch (\Exception $e){
                throw new \Exception( sprintf(">>> exec: %s\n>>> return: %s\n>>> error: %s\n", $cmd, json_encode($ret), $e->getMessage()), 0, $e );
            }
        };

        $public_key_file = $cm->getSshPublicKeyFileByEnv( $client->getEnv() ) ;

        if( !file_exists($public_key_file) ) {
            throw new \Exception( sprintf("ssh public key %s not exists for env `%s`", $public_key_file, $client->getEnv()->getName() ) );
        }

        if( !is_readable($public_key_file) ) {
            throw new \Exception( sprintf("ssh public key %s not readable for env `%s`", $public_key_file, $client->getEnv()->getName() ) );
        }

        if( filesize($public_key_file) > 10240 ) {
            throw new \Exception( sprintf("ssh public key %s is too big for env `%s`", $public_key_file, $client->getEnv()->getName() ) );
        }

        $bootstrap_file   = sprintf('/tmp/knife_bootstrap_%s_%s.sh', time(), rand(10000, 99999)) ;
        $bootstrap_setup_file   = sprintf('/tmp/knife_bootstrap_setup_%s_%s.sh', time(), rand(10000, 99999)) ;

        $result = $this->runLocalScriptOnClient( $client, 'bootstrap', array(
            'authorized_file'  => $public_key_file ,
            'bootstrap_setup_file'    => $bootstrap_setup_file ,
        ) , $bootstrap_file , true , true ) ;

        preg_match_all('/env_client_(.+)=(.+)/', $result, $ls) ;

        $counter    = 0 ;
        if( $ls ) {
            $properties = array() ;
            foreach($ls[1] as $i => $key ) {
                $properties[$key] = $ls[2][$i] ;
            }

            if( isset($properties['home']) ) {
                $counter++ ;
                $client->setHome( $properties['home'] ) ;
            }

            if( isset($properties['hostname']) ) {
                $counter++ ;
                if( $input->getOption('hostname') ||  !$client->getHostname() ) {
                    $client->setHostname( $properties['hostname'] ) ;
                }
            }

            if( isset($properties['bash']) ) {
                $counter++ ;
                if( $input->getOption('bash') ||  !$client->getBashPath() ) {
                    $client->setBashPath( $properties['bash'] ) ;
                }
            }
        }

        if( 3 !== $counter ) {
            throw new \Exception( sprintf("bootstrap %s error:\n %s", $client, $result));
        }

        $result = $this->runRemoteScriptOnClient($client, $bootstrap_setup_file) ;

        $this->setupCookbooks() ;

        if( !$input->getOption('norsync') ) {
            $this->rsyncClientCookbook($client) ;
        }

        if( 0 === $result ) {
            $client->setBootstrap( new \DateTime('now') ) ;
            $output->writeln( sprintf("bootstrap %s finished!", $client)) ;
        } else {
            $client->setBootstrap() ;
            $output->writeln( sprintf("bootstrap %s error!!!", $client)) ;
        }

        $em = $this->getEntityManger() ;
        $em->persist($client) ;
        $em->flush() ;
    }
}