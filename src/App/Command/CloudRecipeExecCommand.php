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
class CloudRecipeExecCommand extends CloudBaseCommand {

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
                new InputOption('setup', null , InputOption::VALUE_OPTIONAL, 'run setup' ) ,
                new InputOption('norsync', null , InputOption::VALUE_NONE, 'skip rsync' ) ,
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
                new InputOption('shell', null , InputOption::VALUE_OPTIONAL, 'ssh shell console ' ) ,
                new InputOption('update', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
            ))
            ->setName('knife:recipe:exec')
            ->setDescription('exec knife client recipe')
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

        $recipe = $this->getCurrentRecipeByName($input->getArgument('client') , $input->getArgument('recipe'), $input->getArgument('name') ) ;
        if( !$recipe ) {
            return ;
        }
        if( !$recipe->getId() ) {
            throw new \Exception("big error");
        }
        $client = $recipe->getClient() ;

        if( null !== $shell ) {
            $ssh_key    = $this->getCloudManger()->getSshPrivateKeyFileByEnv( $client->getEnv() ) ;
            $ssh_host   = $client->getIp() ;
            $ssh_port   = $client->getPort() ;
            $ssh_user   = $client->getUser();

            $commamd    = sprintf('ssh -i %s %s@%s -p%s %s', $ssh_key, $ssh_user , $ssh_host, $ssh_port, $shell );

            $output->writeln( sprintf('>>> shell: %s', $client) );

            $ret = null ;
            passthru($commamd, $ret);
            return ;
        }

        if( $input->getOption('update') ) {
            if( !$this->getYamlConfigure('recipe', $recipe->getRecipeName() , $recipe) ) {
                $output->writeln( sprintf("update %s not finished" , $recipe ));
                return ;
            }
            $em = $this->getEntityManger() ;
            $em->persist($recipe) ;
            $em->flush() ;
            $output->writeln( sprintf("%s updated" , $recipe ) );
            return ;
        }

        $setup = $this->getInputOption('setup', false );
        $this->setupCookbooks($setup) ;

        if( !($input->getOption('norsync') && null === $setup) ) {
            $this->rsyncClientCookbook($client) ;
        }

        $instance   = $this->getCloudManger()->getRecipeConfiguration( $recipe->getRecipeName() ) ;

        $this->_output->writeln( sprintf(">>> exec: %s, recipe: %s:%s ", $recipe , $instance->getCookbookName(), $instance->getRecipeName() ) );

        $recipe->setTryInstall();
        $em = $this->getEntityManger();
        $em->persist($recipe);
        $em->flush();

        $this->execClientRecipe($recipe, $input->getOption('debug') ) ;
    }

    protected function execClientRecipe(\App\Cloud\Entity\Recipe $recipe, $debug ) {

        $instance = $this->getCloudManger()->getRecipeConfiguration( $recipe->getRecipeName() ) ;

        $data = $instance->getRecipeExecuteArray($recipe, $debug , true ) ;

        $client = $recipe->getClient() ;

        if( $recipe->getMultipleName() ) {
            $recipe_config  = sprintf('~/.chefsolo/config/%s_%s_%s_%s.json', $recipe->getRecipeName(), $recipe->getMultipleName(), $client->getId(), $recipe->getId() )  ;
        } else {
            $recipe_config  = sprintf('~/.chefsolo/config/%s_%s_%s.json', $recipe->getRecipeName(), $client->getId(), $recipe->getId() )  ;
        }

        $knife_recipe_scripts   = array() ;
        if( isset($data['knife_recipe_scripts']) ) {
            $knife_recipe_scripts    = $data['knife_recipe_scripts'] ;
            unset($data['knife_recipe_scripts']) ;
        }

        $this->runLocalScriptOnClient($client, 'run_recipe',  array(
            'client'        => $client ,
            'recipe'        => $recipe ,
            'debug'        => $debug ,
            'recipe_config'   => $recipe_config ,
            'recipe_data'   => json_encode($data, JSON_PRETTY_PRINT ) ,
            'recipe_data_bag'   => $data ,
            'knife_recipe_scripts'   => $knife_recipe_scripts ,
        ));

        $recipe->setInstalled();
        $em = $this->getEntityManger();
        $em->persist($recipe);
        $em->flush();
    }
}