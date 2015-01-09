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
class AppSetupCommand extends CloudBaseCommand {

    protected $_work_dir    = null ;
    protected $_root_dir    = null ;

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (version_compare(phpversion(), '5.4.0', '<') || defined('HHVM_VERSION')) {
            return false;
        }

        return parent::isEnabled();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('setup', null , InputOption::VALUE_OPTIONAL, 'run setup' ) ,
                new InputOption('debug', null , InputOption::VALUE_NONE, 'do not do the configure check', null ),
                new InputOption('dump', null , InputOption::VALUE_OPTIONAL, 'dump env', null ),
            ))
            ->setName('knife:setup')
            ->setDescription('knife setup site-cookbooks')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output) ;
        $update = $this->getInputOption('setup', false );
        if( null === $update ) {
            $update = false ;
        }
        $this->setupCookbooks( $update ) ;

        $env_name = $this->getInputOption('dump', $this->getCloudManger()->getCurrentEnvName() ) ;
        if( $env_name ) {
            $env = $this->getCloudManger()->getEnv( $env_name ) ;
            $output->writeln( sprintf("%s:", $env_name) );
            foreach($env->getClients() as $client) {
                $output->writeln( sprintf("\t%s:", $client->getName() ) );
                foreach($client->getRecipes() as $recipe)  if( $recipe instanceof \App\Cloud\Entity\Recipe)  {
                    $name = $recipe->getMultipleName() ;
                    if( $name ) {
                        $name = sprintf('(%s, %s)', $recipe->getRecipeName(), $name);
                    } else {
                        $name = sprintf('(%s)', $recipe->getRecipeName());
                    }
                    $try = $recipe->getTryInstall() ;
                    $done = $recipe->getInstalled() ;

                    $status = array() ;
                    if( $try ) {
                        $status[]   = sprintf("try(%s)", $try->format('mdHis') );
                    }
                    if( $done ) {
                        $status[]   = sprintf('ok(%s)', $done->format('mdHis') );
                    }
                    $output->writeln( sprintf("\t\t%s: \t %s", $name,  join("\t", $status ) ) );
                }
            }
        }
    }


}