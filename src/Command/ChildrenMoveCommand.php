<?php

namespace SQLI\EzToolboxBundle\Command;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Core\Query\QueryFactoryInterface;
use Ibexa\Core\Repository\SearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ChildrenMoveCommand extends Command
{
    /** @var Repository */
    protected $repository;
    /** @var LocationService */
    protected $locationService;
    /** @var QueryFactoryInterface */
    protected $queryFactory;
    /** @var SearchService */
    protected $searchService;
    /** @var int */
    private $currentParentLocationID;
    /** @var int */
    private $newParentLocationID;

    public function __construct(
        QueryFactoryInterface $queryFactory,
        Repository $repository
    ) {
        $this->queryFactory = $queryFactory;
        $this->repository = $repository;
        $this->searchService = $repository->getSearchService();
        $this->locationService = $this->repository->getLocationService();
        parent::__construct('sqli:move:children');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Move all children of "currentParentLocationID" under "newParentLocationID"')
            ->addArgument('current', InputArgument::REQUIRED, "Move children of this locationID")
            ->addArgument('new', InputArgument::REQUIRED, "Move children under this locationID");
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
        $currentLocation = $this->locationService->loadLocation($this->currentParentLocationID);
        // Retrieve new Location
        $newLocation = $this->locationService->loadLocation($this->newParentLocationID);

        // Information
        $output->writeln(sprintf(
            "Move children of <comment>%s</comment> under <comment>%s</comment>",
            $currentLocation->getContentInfo()->name,
            $newLocation->getContentInfo()->name
        ));
        $output->writeln("");

        // Retrieve children to move
        /** @var LocationQuery $childrenQuery */
        $childrenQuery = $this->queryFactory->create(
            'SQLI:LocationChildren',
            ['parent_location_id' => $this->currentParentLocationID]
        );
        $childrenToMove = $this->searchService->findLocations($childrenQuery);

        $output->writeln("Task list :");
        foreach ($childrenToMove->searchHits as $childToMove) {
            /** @var $childToMove Location */
            $output->writeln(sprintf(
                "[locationID : %s] <comment>%s</comment> will be moved",
                $childToMove->id,
                $childToMove->getContentInfo()->name
            ));
        }

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

        // Move each child
        foreach ($childrenToMove->searchHits as $childToMove) {
            /** @var $childToMove Location */
            $output->write(sprintf(
                "[locationID : %s] <comment>%s</comment> moved ",
                $childToMove->id,
                $childToMove->getContentInfo()->name
            ));
            $this->locationService->moveSubtree($childToMove, $newLocation);
            $output->writeln("<info>[OK]</info>");
        }

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

        $this->currentParentLocationID = (int)$input->getArgument('current');
        $this->newParentLocationID = (int)$input->getArgument('new');

        // Load and set Administrator User
        $administratorUser = $this->repository->getUserService()->loadUserByLogin('admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference($administratorUser);
    }
}
