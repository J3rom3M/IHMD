<?php

namespace App\Command;

use App\DatabaseFiller\EntityCreator;
use App\DatabaseFiller\MovieDbClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PopulateDataBaseCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'ihmdb:populate:database';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Populate database with the movie db api';

    /**
     * @var EntityCreator
     */
    private EntityCreator $entityCreator;

    /**
     * @param EntityCreator $entityCreator
     */
    public function __construct(EntityCreator $entityCreator)
    {
        $this->entityCreator = $entityCreator;
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addOption('truncate', 't', InputOption::VALUE_NONE, "truncate db before populate");
        $this->addOption('page', null, InputOption::VALUE_OPTIONAL, 'select number of result page: 1 page = 20 results(default 1)', 1);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @see \Doctrine\DBAL\Driver\Middleware
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityCreator->createAndSaveEntity(null !== $input->getOption('truncate'), (int) $input->getOption('page'), $output);
        $output->writeln(PHP_EOL.'<info>Populating complete !</info>');

        return 0;
    }
}