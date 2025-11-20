<?php

namespace SQLI\EzToolboxBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RemoveCommand extends Command
{
    /** @var Repository */
    protected $repository;
    /** @var ContentService */
    protected $contentService;
    /** @var LocationService */
    protected $locationService;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->contentService = $repository->getContentService();
        $this->locationService = $repository->getLocationService();
        parent::__construct('sqli:object:remove');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws NotFoundException
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(true);

        // Load and set Administrator User
        $administratorUser = $this->repository->getUserService()->loadUserByLogin('admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference($administratorUser);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Remove a content, a location or all contents of a content type')
            ->addOption(
                'content',
                null,
                InputOption::VALUE_OPTIONAL,
                "ContentID to remove"
            )
            ->addOption(
                'location',
                null,
                InputOption::VALUE_OPTIONAL,
                "LocationID to remove"
            )
            ->addOption(
                'contenttype',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                "Remove Contents of this Content Type identifier"
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
        if ($contentID = intval($input->getOption('content'))) {
            $output->write("Remove contentID $contentID : ");
            $this->removeContent($output, $contentID);
        }
        if ($locationID = $input->getOption('location')) {
            $output->write("Remove locationID $locationID : ");
            $this->removeLocation($output, $locationID);
        }
        if ($contentType = $input->getOption('contenttype')) {
            $output->write("Remove Contents of this Content Type $contentType : ");
            // Ask confirmation before removing all contents of a type
            if (!$this->askConfirmation($input, $output)) {
                return self::FAILURE;
            }
            $this->removeContentTypeContents($input, $output, $contentType);
        }

        return self::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param int $contentID
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    private function removeContent(OutputInterface $output, int $contentID): void
    {
        $content = $this->contentService->loadContentInfo($contentID);
        $contentName = $content->name;
        $this->contentService->deleteContent($content);
        $output->writeln("<info>" . $contentName . "</info>");
    }

    /**
     * @param OutputInterface $output
     * @param int $locationID
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    private function removeLocation(OutputInterface $output, int $locationID): void
    {
        $location = $this->locationService->loadLocation($locationID);
        $contentName = $location->getContent()->getName();
        $this->locationService->deleteLocation($location);
        $output->writeln("<info>" . $contentName . "</info>");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $contentTypeIdentifier
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    private function removeContentTypeContents(
        InputInterface $input,
        OutputInterface $output,
        string $contentTypeIdentifier
    ) {
        $query = new Query();
        $query->query = new Query\Criterion\ContentTypeIdentifier($contentTypeIdentifier);
        $searchResults = $this->repository->getSearchService()->findContent($query);
        $totalCount = $searchResults->totalCount;
        unset($searchResults);

        $output->writeln(sprintf("Number of contents to remove : <info>%s</info>", $totalCount));

        $offset = 0;
        while ($offset <= $totalCount) {
            $searchResults = $this->repository->getSearchService()->findContent($query);
            foreach ($searchResults->searchHits as $searchHit) {
                /** @var Content $content */
                $content = $searchHit->valueObject;
                $output->write($content->getName() . " : ");
                $this->repository->getContentService()->deleteContent($content->contentInfo);
                $output->writeln("<info>deleted</info>");
            }
            unset($searchResults);

            $offset += $query->limit;
        }

        $output->writeln("<comment>All contents removed</comment>");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function askConfirmation(InputInterface $input, OutputInterface $output): bool
    {
        // Ask confirmation
        $output->writeln("");
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            '<question>Are you sure you want to proceed [y/N]?</question> ',
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('');

            return false;
        }

        return true;
    }
}
