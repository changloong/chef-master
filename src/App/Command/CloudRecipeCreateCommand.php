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
class CloudRecipeCreateCommand extends CloudBaseCommand {

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('client', InputArgument::OPTIONAL, 'client name', null ) ,
                new InputArgument('recipe', InputArgument::OPTIONAL, 'recipe name', null ) ,
                new InputArgument('name', InputArgument::OPTIONAL, 'recipe multiple name', null ) ,
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:recipe:create')
            ->setDescription('create knife client recipe')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setup($input, $output) ;

        $recipe = $this->getCurrentRecipeByName( $input->getArgument('client')  , $input->getArgument('recipe'), $input->getArgument('name'), null, true ) ;
        if( !$recipe ) {
            return ;
        }
        if( $recipe->getId() ) {
            throw new \Exception( sprintf("%s for %s alrady exists!", $recipe->getRecipeName(), $recipe->getClient()));
        }

        if( !$this->getYamlConfigure('recipe', $recipe->getRecipeName() , $recipe) ) {
            $output->writeln( sprintf("create %s not finished" , $recipe ));
            return ;
        }

        $em = $this->getEntityManger() ;
        $em->persist($recipe) ;
        $em->flush() ;

        $output->writeln( sprintf("%s` created" , $recipe ));

    }
}
