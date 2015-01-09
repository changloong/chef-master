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
class CloudEnvCreateCommand extends CloudBaseCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('name', InputArgument::OPTIONAL, 'env name') ,
                new InputOption('default', null, InputOption::VALUE_NONE, 'default env', null ),
                new InputOption('debug', null, InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:env:create')
            ->setDescription('create knife env')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output) ;

        $name   = $input->getArgument('name') ;

        $env = new \App\Cloud\Entity\Env() ;
        $env->setName($name) ;

        if( !$this->getYamlConfigure('env', 'default', $env ) ) {
            $output->writeln( sprintf("create env %s not finished" , json_encode($name) ));
            return ;
        }

        $em = $this->getEntityManger() ;
        $em->persist($env) ;
        $em->flush() ;


        $output->writeln( sprintf("%s created",   $env ) );

        $cm = $this->getCloudManger() ;
        if(  $input->getOption('default') || !$cm->getCurrentEnvName() ) {
            $cm->setCurrentEnvName( $env->getName() ) ;
            $output->writeln( sprintf("set %s as default",   $env ) );
        }

    }
}
