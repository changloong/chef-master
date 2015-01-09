<?php

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Helper\HelperSet;

use My\Command\MyCommand;
use Knp\Console\ConsoleEvents;
use Knp\Console\ConsoleEvent;

$app['dispatcher']->addListener(ConsoleEvents::INIT, function(ConsoleEvent $event) {
    $console = $event->getApplication();
    $app    = $console->getSilexApplication() ;

    $helperSet = new HelperSet(array(
        'db' => new ConnectionHelper($app['db.orm.em']->getConnection()),
        'em' => new EntityManagerHelper($app['db.orm.em']) ,
    ));

    $helperSet->set( new \Symfony\Component\Console\Helper\DebugFormatterHelper() );
    $helperSet->set( new \Symfony\Component\Console\Helper\DescriptorHelper() );
    $helperSet->set( new \Symfony\Component\Console\Helper\DialogHelper() );
    $helperSet->set( new \Symfony\Component\Console\Helper\FormatterHelper() );
    $helperSet->set( new \Symfony\Component\Console\Helper\ProcessHelper() );
    $helperSet->set( new \Symfony\Component\Console\Helper\ProgressHelper() );
    $helperSet->set( new \Symfony\Component\Console\Helper\QuestionHelper() );
    $helperSet->set( new \Symfony\Component\Console\Helper\TableHelper() );

    $console->setHelperSet($helperSet);

    $console->add( new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand() ) ;
    $console->add( new \Doctrine\DBAL\Tools\Console\Command\ImportCommand() ) ;
    $console->add( new \Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand() ) ;

    $console->add( new \Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand() ) ;


    $console->add( new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand() ) ;

    $console->add( new \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\InfoCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand() ) ;
    $console->add( new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand() ) ;

});
