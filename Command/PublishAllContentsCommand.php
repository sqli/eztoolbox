<?php

namespace SQLI\EzToolboxBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class PublishAllContentsCommand extends Command
{
    public const FETCH_LIMIT = 25;
    /** @var string */
    protected $contentClassIdentifier;
    /** @var Repository */
    protected $repository;
    /** @var SearchService */
    protected $searchService;
    /** @var ContentService */
    protected $contentService;
    /** @var int */
    protected $totalCount;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->searchService = $repository->getSearchService();
        $this->contentService = $repository->getContentService();
        parent::__construct('sqli:object:republish');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(true);
        $input->setInteractive(true);

        $this->contentClassIdentifier = $input->getArgument('contentClassIdentifier');

        // Load and set Administrator User for permissions
        $administratorUser = $this->repository->getUserService()->loadUserByLogin('admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference($administratorUser);

        // Count number of contents to update
        $this->totalCount = $this->fetchCount();
    }

    /**
     * Returns number of contents who will be updated
     *
     * @return int
     * @throws InvalidArgumentException
     */
    private function fetchCount(): int
    {
        $this->searchService = $this->repository->getSearchService();

        // Prepare count
        $query = new LocationQuery();

        $query->query = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier($this->contentClassIdentifier),
        ]);
        $query->performCount = true;
        $query->limit = 0;
        $results = $this->searchService->findContent($query);

        return (int)$results->totalCount;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Publish all contents of the ContentType with specified identifier')
            ->addArgument('contentClassIdentifier', InputArgument::REQUIRED, "ContentType identifier");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws BadStateException
     * @throws UnauthorizedException
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf(
            "Fetching all objects of contentType '<comment>%s</comment>'",
            $this->contentClassIdentifier
        ));

        // Informations
        $output->writeln("<comment>{$this->totalCount}</comment> contents found");

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

        $offset = 0;
        do {
            // Fetch small group of contents
            $items = $this->fetch(self::FETCH_LIMIT, $offset);

            // Publish each content
            foreach ($items as $index => $content) {
                /** @var $content Content */
                $contentDraft = $this->contentService->createContentDraft($content->getVersionInfo()->getContentInfo());
                $this->contentService->publishVersion($contentDraft->getVersionInfo());

                $output->writeln(sprintf(
                    "[%s/%s] contentID: %s <comment>%s</comment> published",
                    ($offset + $index + 1),
                    $this->totalCount,
                    $content->id,
                    $content->getName()
                ));
            }

            $offset += self::FETCH_LIMIT;
        } while ($offset < $this->totalCount);

        $output->writeln("");
        $output->writeln("<info>Job finished !</info>");

        return self::SUCCESS;
    }

    /**
     * Fetch contents with offset and limit
     *
     * @param     $limit
     * @param int $offset
     * @return array
     * @throws InvalidArgumentException
     */
    private function fetch($limit, $offset = 0): array
    {
        $this->searchService = $this->repository->getSearchService();

        // Prepare fetch with offset and limit
        $query = new LocationQuery();

        $query->query = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier($this->contentClassIdentifier),
        ]);
        $query->performCount = true;
        $query->limit = $limit;
        $query->offset = $offset;
        $results = $this->searchService->findContent($query);
        $items = [];

        // Prepare an array with contents
        foreach ($results->searchHits as $item) {
            $items[] = $item->valueObject;
        }

        return $items;
    }
}
