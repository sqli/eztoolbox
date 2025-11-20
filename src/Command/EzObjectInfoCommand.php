<?php

namespace SQLI\EzToolboxBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EzObjectInfoCommand extends Command
{
    /** @var Repository */
    protected $repository;
    /** @var ContentService */
    protected $contentService;
    /** @var LocationService */
    protected $locationService;
    /** @var SearchService */
    protected $searchService;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->contentService = $this->repository->getContentService();
        $this->locationService = $this->repository->getLocationService();
        $this->searchService = $this->repository->getSearchService();
        parent::__construct('sqli:object:info');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws NotFoundException
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);

        // Load and set Administrator User for permissions
        $administratorUser = $this->repository->getUserService()->loadUserByLogin('admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference($administratorUser);
    }

    protected function configure()
    {
        $this
            ->setDescription('Display informations of specified content or location')
            ->addOption(
                'content',
                null,
                InputOption::VALUE_OPTIONAL,
                "Display informations of specified content"
            )
            ->addOption(
                'location',
                null,
                InputOption::VALUE_OPTIONAL,
                "Display informations of specified location"
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws NotFoundException
     * @throws UnauthorizedException|InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($locationId = $input->getOption('location')) {
            $this->displayLocationInfo($output, $locationId);
        } elseif ($contentId = $input->getOption('content')) {
            $this->displayContentInfo($output, $contentId);
        }

        return self::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param $locationId
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    private function displayLocationInfo(OutputInterface $output, $locationId): void
    {
        $location = $this->locationService->loadLocation($locationId);

        // Display location informations
        $output->writeln("Location ID : $locationId");
        $output->writeln("Location name : " . $location->getContentInfo()->name);
        $output->writeln("Location path string : " . $location->pathString);
        $output->writeln("Location ancestors :");
        foreach ($location->path as $ancestorId) {
            $ancestorLocation = $this->locationService->loadLocation($ancestorId);
            $output->writeln("  $ancestorId : " . $ancestorLocation->getContentInfo()->name);
        }
    }

    /**
     * @param OutputInterface $output
     * @param $contentId
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws InvalidArgumentException
     */
    private function displayContentInfo(OutputInterface $output, $contentId): void
    {
        $content = $this->contentService->loadContent($contentId);
        $mainLocationId = $content->contentInfo->mainLocationId;

        // Display content informations
        $output->writeln("<comment>Content ID :</comment> $contentId\n");
        $output->writeln("<comment>Main location :</comment>");
        $this->displayLocationInfo($output, $mainLocationId);

        // Search other locations
        $query = new LocationQuery();
        $criterion[] = new Criterion\ContentId($contentId);
        $query->query = new Criterion\LogicalAnd($criterion);

        $results = $this->searchService->findLocations($query);

        // Display locations
        $output->writeln("\n<comment>Other locations :</comment>");
        foreach ($results->searchHits as $locationFound) {
            $location = $locationFound->valueObject;
            if (
                $location instanceof Location &&
                $location->id != $mainLocationId
            ) {
                // It's not main location, display it
                $this->displayLocationInfo($output, $location->id);
                $output->writeln("");
            }
        }
    }
}
