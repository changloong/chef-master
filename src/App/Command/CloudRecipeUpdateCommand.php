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
class CloudRecipeUpdateCommand extends CloudBaseCommand {

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
            ->setName('knife:recipe:update')
            ->setDescription('update knife client recipe')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->setup($input, $output) ;

        $recipe = $this->getCurrentRecipeByName($input->getArgument('client'), $input->getArgument('recipe'), $input->getArgument('name') ) ;
        if( !$recipe ) {
            return ;
        }
        if( !$recipe->getId() ) {
            throw new \Exception("big error");
        }

        // \Dev::dump($recipe->getDataBag()['site']);

        if( !$this->getYamlConfigure('recipe', $recipe->getRecipeName() , $recipe) ) {
            $output->writeln( sprintf("update %s not finished" , $recipe ));
            return ;
        }

        $em = $this->getEntityManger() ;
        $em->persist($recipe) ;
        $em->flush() ;

        $output->writeln( sprintf("%s updated" , $recipe ) );

    }
}