<?php

namespace SQLI\EzToolboxBundle\Command;

use DateTime;
use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeDraftsCommand extends Command
{
    public const FETCH_LIMIT = 25;
    /** @var ContentService */
    protected $contentService;
    /** @var SearchService */
    protected $searchService;
    /** @var Repository */
    protected $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->searchService = $repository->getSearchService();
        $this->contentService = $repository->getContentService();
        parent::__construct('sqli:purge:drafts');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws NotFoundException
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(true);

        // Load and set Administrator User for permissions
        $administratorUser = $this->repository->getUserService()->loadUserByLogin('admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference($administratorUser);
    }

    protected function configure(): void
    {
        $this->setDescription('Purge orphan drafts');
    }

    /**
     * Purge orphan drafts
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Count all contents
        $query = new Query();
        $query->offset = 0;
        $query->limit = 0;
        $query->query = new Criterion\MatchAll();
        $query->performCount = true;
        $findResults = $this->searchService->findContentInfo($query);
        $totalCount = $findResults->totalCount;
        $index = 1;

        $output->writeln("Number of contents to scan : <info>{$totalCount}</info>");

        // Fetch contents with offset and limit
        while ($query->offset <= $totalCount) {
            $query->limit = self::FETCH_LIMIT;
            $query->performCount = false;

            // Fetch content infos
            $findResults = $this->searchService->findContentInfo($query);

            foreach ($findResults->searchHits as $hitResult) {
                $contentInfo = $hitResult->valueObject;
                /** @var ContentInfo $contentInfo */
                // Retrieve all versions for each contentInfo
                $versions = $this->contentService->loadVersions($contentInfo);
                // Check each versions
                foreach ($versions as $version) {
                    /** @var VersionInfo $version */
                    $date = new DateTime('-1 week');
                    // If version is a draft and modification date is greater than 1 week then it's an orphan draft
                    if ($version->isDraft() && $version->modificationDate < $date) {
                        $output->writeln(sprintf(
                            '[%d/%d] ContentID %d : <comment>%s</comment>',
                            $index,
                            $totalCount,
                            $contentInfo->id,
                            $contentInfo->name
                        ));
                        $output->write("     - DraftID {$version->id} ");

                        // Remove old draft
                        $output->write("<comment>must be purged</comment> : ");

                        $success = false;
                        try {
                            // Try to delete orphan version
                            $this->contentService->deleteVersion($version);
                            $success = true;
                        } catch (Exception $exception) {
                            // Do nothing
                        }

                        if ($success) {
                            $output->write("<info>OK</info>");
                        } else {
                            $output->write("<error>FAILED !</error>");
                        }
                        $output->writeln("");
                    }
                }
                $index++;
            }

            // Modify offset for next iteration
            $query->offset += self::FETCH_LIMIT;
        }

        return self::SUCCESS;
    }
}
