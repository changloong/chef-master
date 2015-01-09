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
class CloudClientCreateCommand extends CloudBaseCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('name', InputArgument::OPTIONAL, 'client name', null ) ,
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:client:create')
            ->setDescription('create knife client')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setup($input, $output) ;

        $env = $this->getCurrentEnv() ;
        if( !$env ) {
            return ;
        }

        $client = new \App\Cloud\Entity\Client() ;
        $client->setEnv($env) ;
        $client->setName( $input->getArgument('name') ) ;

        if( !$this->getYamlConfigure('client', 'default', $client) ) {
            $output->writeln( sprintf("create %s not finished" , $client )) ;
            return ;
        }

        $em = $this->getEntityManger() ;
        $em->persist($client) ;
        $em->flush() ;

        $output->writeln( sprintf("%s created",  $client ) ) ;

    }
}
