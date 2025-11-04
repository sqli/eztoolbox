<?php

namespace SQLI\EzToolboxBundle\Command;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\ContentId;
use Ibexa\Core\FieldType\DateAndTime\Value;
use Ibexa\FieldTypeRichText\FieldType\RichText\Type;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CopyContentFieldAllContentsCommand extends Command
{
    public const FETCH_LIMIT = 25;
    /** @var Repository */
    protected $repository;
    /** @var SearchService */
    protected $searchService;
    /** @var ContentService */
    protected $contentService;
    /** @var Type */
    protected $richtextType;
    /** @var string */
    private $oldContentFieldIdentifier;
    /** @var string */
    private $newContentFieldIdentifier;
    /** @var string */
    private $contentClassIdentifier;
    /** @var bool */
    private $dryrun;
    /** @var int */
    private $totalCount;

    public function __construct(
        Repository $repository,
        Type $richtextType
    ) {
        $this->repository = $repository;
        $this->searchService = $repository->getSearchService();
        $this->contentService = $repository->getContentService();
        $this->richtextType = $richtextType;
        parent::__construct('sqli:object:field_copy');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws NotFoundException|InvalidArgumentException
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(true);
        $input->setInteractive(true);

        $this->contentClassIdentifier = $input->getArgument('contentClassIdentifier');
        $this->oldContentFieldIdentifier = $input->getArgument('oldContentFieldIdentifier');
        $this->newContentFieldIdentifier = $input->getArgument('newContentFieldIdentifier');
        $this->dryrun = $input->hasParameterOption(array(
            '--dry-run',
            '-d'
        ), true);

        // Load and set Administrator User for permissions
        $administratorUser = $this->repository->getUserService()->loadUserByLogin('admin');
        $this->repository->getPermissionResolver()->setCurrentUserReference($administratorUser);

        // Count number of contents to update
        $this->totalCount = $this->fetchCount();
    }

    /**
     * Returns number of contents who will be updated
     *
     * @param string $languageCode
     * @return int
     * @throws InvalidArgumentException
     */
    private function fetchCount($languageCode = "fre-FR"): int
    {
        $this->searchService = $this->repository->getSearchService();

        // Prepare count
        $query = new LocationQuery();

        $query->query = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier($this->contentClassIdentifier),
            new Criterion\LanguageCode($languageCode, false),
        ]);
        $query->performCount = true;
        $query->limit = 0;
        $results = $this->searchService->findContent($query);

        return (int)$results->totalCount;
    }

    protected function configure(): void
    {
        $description = "Copy value of a ContentField to a new ContentField ";
        $description .= "for all existing contents of specific ContentType\n";
        $description .= "WARNING : Specific types not yet supported (XML, Image, ...)";
        $this
            ->setDescription($description)
            ->addArgument(
                'contentClassIdentifier',
                InputArgument::REQUIRED,
                "ContentType identifier"
            )
            ->addArgument(
                'oldContentFieldIdentifier',
                InputArgument::REQUIRED,
                "Original ContentField identifier"
            )
            ->addArgument(
                'newContentFieldIdentifier',
                InputArgument::REQUIRED,
                "Target ContentField identifier"
            )
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, "Simulation mode");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
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

        $availableLanguages = $this->repository->getContentLanguageService()->loadLanguages();

        foreach ($availableLanguages as $availableLanguage) {
            $availableLanguageCode = $availableLanguage->languageCode;
            $output->writeln("Starting job for <info>$availableLanguageCode</info> :");
            $this->totalCount = $this->fetchCount($availableLanguageCode);
            $offset = 0;

            do {
                // Fetch small group of contents
                $items = $this->fetch(self::FETCH_LIMIT, $offset, $availableLanguageCode);

                // Publish each content
                foreach ($items as $index => $content) {
                    /** @var $content Content */
                    $output->write(sprintf(
                        "[%s/%s][%s] contentID: %s <comment>%s</comment> ",
                        ($offset + $index + 1),
                        $this->totalCount,
                        $availableLanguageCode,
                        $content->id,
                        $content->getName()
                    ));

                    // Create draft
                    $contentDraft = $this->contentService->createContentDraft(
                        $content->getVersionInfo()->getContentInfo()
                    );
                    // Prepare update
                    $contentStructure = $this->contentService->newContentUpdateStruct();

                    // Get value of old field
                    switch (
                        $contentDraft->getField(
                            $this->oldContentFieldIdentifier,
                            $availableLanguageCode
                        )->fieldTypeIdentifier
                    ) {
                        case "ezdate":
                            /** @var \Ibexa\Core\FieldType\Date\Value $fieldValue */
                            $fieldValue = $contentDraft->getFieldValue(
                                $this->oldContentFieldIdentifier,
                                $availableLanguageCode
                            );
                            $valueToCopy = $fieldValue->date;
                            break;
                        case "ezdatetime":
                            /** @var Value $fieldValue */
                            $fieldValue = $contentDraft->getFieldValue(
                                $this->oldContentFieldIdentifier,
                                $availableLanguageCode
                            );
                            $valueToCopy = $fieldValue->value;
                            break;
                        default:
                            $valueToCopy = $contentDraft->getFieldValue(
                                $this->oldContentFieldIdentifier,
                                $availableLanguageCode
                            )->__toString();
                            break;
                    }

                    $update = true;
                    // Format data value according to field type
                    switch (
                        $contentDraft->getField(
                            $this->newContentFieldIdentifier,
                            $availableLanguageCode
                        )->fieldTypeIdentifier
                    ) {
                        case "ezrichtext":
                            $xmlvalueToCopy = "<section xmlns=\"http://ez.no/namespaces/ezpublish5/xhtml5/edit\"><p>";
                            $xmlvalueToCopy .= $valueToCopy;
                            $xmlvalueToCopy .= "</p></section>";
                            $valueToCopy = $this->richtextType->acceptValue($xmlvalueToCopy);

                            $oldValueInNewField = $contentDraft->getFieldValue(
                                $this->newContentFieldIdentifier,
                                $availableLanguageCode
                            )->__toString();
                            if ($valueToCopy == $oldValueInNewField) {
                                $update = false;
                            }
                            break;
                        case "eztagco":
                            $contentJson = json_decode($valueToCopy);
                            if (!is_null($contentJson) && property_exists($contentJson, 'url')) {
                                $valueToCopy = $contentJson->url;
                            } else {
                                continue 2;
                            }
                            break;
                    }

                    if ($update) {
                        if (!$this->dryrun) {
                            try {
                                // Set value on new field
                                $contentStructure->setField(
                                    $this->newContentFieldIdentifier,
                                    $valueToCopy,
                                    $availableLanguageCode
                                );

                                // Update draft
                                $contentDraft = $this->contentService->updateContent(
                                    $contentDraft->getVersionInfo(),
                                    $contentStructure
                                );

                                // Publish draft
                                $this->contentService->publishVersion($contentDraft->getVersionInfo());

                                $output->writeln("modified");
                            } catch (Exception $exception) {
                                $output->writeln("<error>failed</error>");
                            }
                        }
                    } else {
                        $output->writeln("");
                    }
                }

                $offset += self::FETCH_LIMIT;
            } while ($offset < $this->totalCount);
            $output->writeln("");
        }

        if ($this->dryrun) {
            $output->writeln("<question>Mode dry-run, no content updated</question>");
        }
        $output->writeln("");
        $output->writeln("<info>Job finished !</info>");

        return self::SUCCESS;
    }

    /**
     * Fetch contents with offset and limit
     *
     * @param int $limit
     * @param int $offset
     * @param string $languageCode
     * @return array
     * @throws InvalidArgumentException
     */
    private function fetch(int $limit, int $offset = 0, $languageCode = "fre-FR"): array
    {
        $this->searchService = $this->repository->getSearchService();

        // Prepare fetch with offset and limit
        $query = new LocationQuery();

        $query->query = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier($this->contentClassIdentifier),
            new Criterion\LanguageCode($languageCode, false),
        ]);
        $query->performCount = true;
        $query->limit = $limit;
        $query->offset = $offset;
        $query->sortClauses = [new ContentId()];
        $results = $this->searchService->findContent($query);
        $items = [];

        // Prepare an array with contents
        foreach ($results->searchHits as $item) {
            $items[] = $item->valueObject;
        }

        return $items;
    }
}
