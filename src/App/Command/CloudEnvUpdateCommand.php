<?php

namespace App\Command ;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @App\Cloud\Annotation\Command()
 */
class CloudEnvUpdateCommand extends CloudBaseCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('name', InputArgument::OPTIONAL, 'env name') ,
                new InputOption('default', 'd', InputOption::VALUE_NONE, 'default env', null ),
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:env:update')
            ->setDescription('update knife env')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setup($input, $output) ;

        $env = $this->getCurrentEnv( $input->getArgument('name') , null, false ) ;

        if( !$env ) {
            return ;
        }

        if( !$this->getYamlConfigure('env', 'default', $env) ) {
            $output->writeln( sprintf("update %s not finished" , $env ));
            return ;
        }

        $em = $this->getEntityManger() ;
        $em->persist($env) ;
        $em->flush() ;

        $output->writeln( sprintf("%s updated",  $env ) ) ;

        $cm = $this->getCloudManger() ;
        if(  $input->getOption('default') || !$cm->getCurrentEnvName() ) {
            $cm->setCurrentEnvName( $env->getName() ) ;
            $output->writeln( sprintf("set `%s` as default",   $env ) );
        }

    }
}

