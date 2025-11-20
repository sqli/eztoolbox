<?php

namespace SQLI\EzToolboxBundle\Services\Twig;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\View\ViewManagerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SQLI\EzToolboxBundle\Services\FetchHelper;
use SQLI\EzToolboxBundle\Services\Formatter\SqliSimpleLogFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FetchExtension extends AbstractExtension
{
    protected LoggerInterface $logger;

    public function __construct(
        protected FetchHelper $fetchHelper,
        protected ViewManagerInterface $viewManager,
        protected Repository $repository,
        $logDir
    ) {
        $handler = new StreamHandler("$logDir/sqli-eztoolbox_" . date("Y-m-d") . '.log');
        $handler->setFormatter(new SqliSimpleLogFormatter());
        $this->logger = new Logger('SQLILogException');
        $this->logger->pushHandler($handler);
    }

    public function getFunctions()
    {
        return
            [
                new TwigFunction('render_children', [$this, 'renderChildren'], ['is_safe' => ['all']]),
                new TwigFunction('fetch_children', [$this->fetchHelper, 'fetchChildren']),
                new TwigFunction('fetch_ancestor', [$this->fetchHelper, 'fetchAncestor']),
                new TwigFunction('fetch_ancestors', [$this->fetchHelper, 'fetchAncestors']),
                new TwigFunction('fetch_content', [$this->repository->getContentService(), 'loadContent']),
                new TwigFunction('fetch_location', [$this->repository->getLocationService(), 'loadLocation']),
            ];
    }

    /**
     * Use ViewController:viewLocation to generate display of children
     * (eventually filtered with $filterContentClass) of a $location in specified $viewType
     * Some $parameters can be passed to template
     *
     * @param Location|int $parentLocation
     * @param string $viewType
     * @param string|string[]|null $filterContentClass
     * @param array $parameters
     * @return string
     * @throws InvalidArgumentException
     */
    public function renderChildren(
        $parentLocation,
        string $viewType = ViewManagerInterface::VIEW_TYPE_LINE,
        $filterContentClass = null,
        array $parameters = array()
    ): string {
        $render = '';
        $limit = $parameters['limit'] ?? FetchHelper::LIMIT;
        $offset = $parameters['offset'] ?? 0;
        // Fetch children of $location
        $children = $this->fetchHelper->fetchChildren(
            $parentLocation,
            $filterContentClass,
            $limit,
            $offset
        );
        end($children);
        $lastKey = key($children);
        reset($children);
        $firstKey = key($children);

        // Define specific parameters
        $parameters['col'] = count($children);

        foreach ($children as $index => $child) {
            $parameters['isFirst'] = $index === $firstKey;
            $parameters['isLast'] = $index === $lastKey;
            $parameters['index'] = $index;

            try {
                $content = $child->getContent();
                $parameters['location'] = $child;
                $parameters['viewType'] = $viewType;
                $parameters['layout'] = false;
                $contentRender = $this->viewManager->renderContent(
                    $content,
                    $viewType,
                    $parameters
                );
                $render .= $contentRender;
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage(), ['code' => $exception->getCode()]);
                continue;
            }
        }

        return $render;
    }
}
