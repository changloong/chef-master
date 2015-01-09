<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @App\Cloud\Annotation\Command()
 */
class CloudClientExecCommand extends CloudBaseCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('client', InputArgument::OPTIONAL, 'client name', null ) ,
                new InputOption('shell', null , InputOption::VALUE_OPTIONAL, 'ssh shell console ' ) ,
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:client:exec')
            ->setDescription('exec command on knife client')
            ->ignoreValidationErrors()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output) ;

        $shell    = $this->getInputOption('shell', '' ) ;
        $cmd    = $this->getIgnoreInputArguments($input) ;
        if( null !== $shell ) {
            if( count($cmd) ) {
                throw new \Exception( sprintf('unknow input option %s', join(' ', $cmd)) );
                return ;
            }
        }

        $client_name = $input->getArgument('client') ;
        $client = $this->getCurrentClientByName( $client_name ) ;
        if( !$client ) {
            return ;
        }

        if( !$client->getBootstrap() ) {
            $output->writeln( sprintf("<error>%s not bootstrap yet!</error>" , $client ));
            return ;
        }

        $cm     = $this->getCloudManger() ;
        $ssh    = $cm->getSshByClient($client) ;

        $exec   = $ssh->getExec() ;
        $run    = function($cmd) use($exec, $output){
            $ret    = null ;
            $out    = null ;
            try {
                $out = $exec->run( $cmd , $ret ) ;
                if( $ret ) {
                    throw new \Exception( sprintf(">>> exec: %s\n>>> return: %s\n>>> output: %s\n", $cmd, json_encode($ret), $out));
                }
            } catch (\Exception $e){
                throw new \Exception( sprintf(">>> exec: %s\n>>> return: %s\n>>> error: %s\n", $cmd, json_encode($ret), $e->getMessage()), 0, $e );
            }
            if( null !== $out ) {
                return $out ;
            }
        };

        if( null !== $shell ) {
            $ssh_key    = $cm->getSshPrivateKeyFileByEnv( $client->getEnv() ) ;
            $ssh_host   = $client->getIp() ;
            $ssh_port   = $client->getPort() ;
            $ssh_user   = $client->getUser();

            $commamd    = sprintf('ssh -i %s %s@%s -p%s %s', $ssh_key, $ssh_user , $ssh_host, $ssh_port, $shell );

            $output->writeln( sprintf('>>> shell: %s', $client) );

            $ret = null ;
            passthru($commamd, $ret);
            return ;
        }

        if( !empty($cmd) ) {
            $_cmd = join(' ', $cmd );
            $_out = $run( $_cmd);
            $output->writeln( sprintf(">>> exec: %s\n>>> output: %s", $_cmd, $_out)) ;
            return ;
        }


        $dialog = $this->getHelper('dialog');

        $hostname   = $client->getHostname() ;
        if( ! $hostname ) {
            $hostname   = $client->getName() ;
        }

        while( true ) {

            $prompt = sprintf('[%s %s@%s ~] $ ', $client->getEnv()->getName() , $client->getUser(), $hostname) ;

            $cmd = trim( $dialog->ask($output, $prompt ) ) ;
            $cmd = rtrim($cmd,';') ;
            $cmd = rtrim($cmd) ;

            if( empty($cmd) ) {
                continue ;
            }

            $_out = $run($cmd);
            if( null === $_out ) {
                break ;
            }
            $this->_output->writeln($_out) ;
            if(  'exit' === $cmd  ) {
                break ;
            }
        }

    }

}