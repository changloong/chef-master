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
class ServerCommand extends \Knp\Command\Command {

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
                new InputArgument('address', InputArgument::OPTIONAL, 'Address:port', '127.0.0.1:8000'),
                new InputOption('docroot', 'd', InputOption::VALUE_REQUIRED, 'Document root', 'web/'),
                new InputOption('router', 'r', InputOption::VALUE_REQUIRED, 'Path to custom router script'),
            ))
            ->setName('server:run')
            ->setDescription('Runs PHP built-in web server')
            ->setHelp(<<<EOF
The <info>%command.name%</info> runs PHP built-in web server:

  <info>%command.full_name%</info>

To change default bind address and port use the <info>address</info> argument:

  <info>%command.full_name% 127.0.0.1:8080</info>

To change default docroot directory use the <info>--docroot</info> option:

  <info>%command.full_name% --docroot=htdocs/</info>

If you have custom docroot directory layout, you can specify your own
router script using <info>--router</info> option:

  <info>%command.full_name% --router=app/config/router.php</info>

Specifing a router script is required when the used environment is not "dev" or
"prod".

See also: http://www.php.net/manual/en/features.commandline.webserver.php

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentRoot = $input->getOption('docroot');

        if (!is_dir($documentRoot)) {
            $output->writeln(sprintf('<error>The given document root directory "%s" does not exist</error>', $documentRoot));

            return 1;
        }

        $app    =  $this->getSilexApplication() ;
        $env = $app['env'] ;

        if ('prod' === $env) {
            $output->writeln('<error>Running PHP built-in server in production environment is NOT recommended!</error>');
        }

        $output->writeln(sprintf("Server running on <info>http://%s</info>\n", $input->getArgument('address')));

        $router = sprintf('%s/app/Resources/src/router_%s.php', $app['root.dir'], $env) ;
        $builder = $this->createPhpProcessBuilder($input, $output, $router);

        $builder->setWorkingDirectory($documentRoot);
        $builder->setTimeout(null);
        $process = $builder->getProcess();

        if (OutputInterface::VERBOSITY_VERBOSE > $output->getVerbosity()) {
            $process->disableOutput();
        }

        $this
            ->getHelper('process')
            ->run($output, $process, null, null, OutputInterface::VERBOSITY_VERBOSE);

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Built-in server terminated unexpectedly</error>');

            if ($process->isOutputDisabled()) {
                $output->writeln('<error>Run the command again with -v option for more details</error>');
            }
        }

        return $process->getExitCode();
    }

    private function createPhpProcessBuilder(InputInterface $input, OutputInterface $output, $router)
    {
        return new ProcessBuilder(array(PHP_BINARY, '-S', $input->getArgument('address'), $router));
    }
}