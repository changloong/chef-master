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
class CloudEnvBootstrapCommand extends CloudBaseCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('name', InputArgument::REQUIRED, 'env name') ,
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:env:boostrap')
            ->setDescription('boostrap knife env')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output) ;

        $name   = $input->getArgument('name') ;
        $env = $this->getCurrentEnv( $name ) ;
        if( !$env ) {
            return ;
        }
        $cm = $this->getCloudManger() ;

        $error  = 0 ;

        $pub    = $cm->getSshPublicKeyFileByEnv($env) ;
        if( !file_exists($pub) ) {
            $output->writeln( sprintf(">>> Error: <error>env `%s` public ssh key file `%s` not exists!</error>",  $env->getName(), $pub ));
            $error  = __LINE__ ;
        } else {
            $this->forceFileMode($pub, 0600) ;
        }
        $pem    = $cm->getSshPrivateKeyFileByEnv($env) ;
        if( !file_exists($pem) ) {
            $output->writeln( sprintf(">>> Error: <error>env `%s` private ssh key file `%s` not exists!</error>",  $env->getName(), $pub ));
            $error  = __LINE__ ;
        } else {
            $this->forceFileMode($pem, 0600) ;
        }

        if( $error ) {
            $output->writeln( sprintf("<error>bootstrap %s not finished!</error>", $env)) ;
        } else {
            $output->writeln( sprintf("bootstrap %s finished!", $env)) ;
        }

    }
}
