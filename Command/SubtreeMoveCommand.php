<?php

namespace SQLI\EzToolboxBundle\Command;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\SearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class SubtreeMoveCommand extends Command
{
    /** @var Repository */
    protected $repository;
    /** @var LocationService */
    protected $locationService;
    /** @var SearchService */
    protected $searchService;
    /** @var int */
    private $currentLocationID;
    /** @var int */
    private $newParentLocationID;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->searchService = $repository->getSearchService();
        $this->locationService = $repository->getLocationService();
        parent::__construct('sqli:move:subtree');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Move "currentParentLocationID" and it\'s subtree under "newParentLocationID"')
            ->addArgument(
                'current',
                InputArgument::REQUIRED,
                "Move this locationID and it's subtree"
            )
            ->addArgument(
                'new',
                InputArgument::REQUIRED,
                "Move children under this locationID"
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Retrieve current Location
        $currentLocation = $this->locationService->loadLocation($this->currentLocationID);
        // Retrieve new Location
        $newLocation = $this->locationService->loadLocation($this->newParentLocationID);

        // Information
        $output->writeln(sprintf(
            "Move <comment>%s</comment> under <comment>%s</comment>",
            $currentLocation->getContentInfo()->name,
            $newLocation->getContentInfo()->name
        ));
        $output->writeln("");

        // Ask confirmation
        $output->writeln("");
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            '<question>Are you sure you want to proceed [y/N]?</question> ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('');

            return self::FAILURE;
        }

        $output->writeln("");
        $output->writeln("Starting job :");

        $output->write(sprintf(
            "[locationID : %s] <comment>%s</comment> moved ",
            $currentLocation->id,
            $currentLocation->getContentInfo()->name
        ));
        $this->locationService->moveSubtree($currentLocation, $newLocation);
        $output->writeln("<info>[OK]</info>");

        $output->writeln("");
        $output->writeln("<info>Job finished !</info>");

        return self::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws NotFoundException
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(true);
        $input->setInteractive(true);

        $this->currentLocationID = (int)$input->getArgument('current');
        $this->newParentLocationID = (int)$input->getArgument('new');

        // Load and set Administrator User
        $administratorUser = $this->repository->getUserService()->loadUserByLogin('admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference($administratorUser);
    }
}
