<?php

namespace SQLI\EzToolboxBundle\Services\Twig;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Templating\Twig\Extension\ContentExtension;
use SQLI\EzToolboxBundle\Services\DataFormatterHelper;
use SQLI\EzToolboxBundle\Services\FieldHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigFilterExtension extends AbstractExtension
{
    /** @var Repository */
    private $repository;
    /** @var DataFormatterHelper */
    private $dataFormatterHelper;
    /** @var ConfigResolverInterface */
    private $configResolver;
    /** @var FieldHelper */
    private $fieldHelper;
    /** @var ContentExtension */
    private $contentExtension;
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(
        Repository $repository,
        DataFormatterHelper $dataFormatterHelper,
        ConfigResolverInterface $configResolver,
        FieldHelper $fieldHelper,
        ContentExtension $contentExtension,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->repository = $repository;
        $this->dataFormatterHelper = $dataFormatterHelper;
        $this->configResolver = $configResolver;
        $this->fieldHelper = $fieldHelper;
        $this->contentExtension = $contentExtension;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getFunctions()
    {
        return
            [
                new TwigFunction('format_data', [$this->dataFormatterHelper, 'format']),
                new TwigFunction('empty_field', [$this, 'isEmptyField']),
                new TwigFunction('is_anonymous_user', [$this, 'isAnonymousUser']),
                new TwigFunction('content_name', [$this, 'getContentName']),
                new TwigFunction('ez_parameter', [$this, 'getParameter']),
                new TwigFunction('has_access', [$this, 'hasAccess']),
                new TwigFunction('ez_selection_value', [$this->fieldHelper, 'ezselectionSelectedOptionValue']),
            ];
    }

    public function getFilters()
    {
        return
            [
                new TwigFilter('format_data', [
                    $this->dataFormatterHelper,
                    'format'
                ]),
            ];
    }

    /**
     * Search a variable/parameter in eZ namespace
     *
     * @param $parameterName
     * @param $namespace
     * @return |null
     */
    public function getParameter($parameterName, $namespace)
    {
        try {
            return $this->configResolver->getParameter($parameterName, $namespace);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks if a given field is considered empty.
     * This method accepts field as Objects or by identifiers.
     *
     * @param Content $content
     * @param Field|string $fieldDefIdentifier Field or Field Identifier to get the value from.
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR").
     *                                     Null by default (takes current locale).
     *
     * @return bool
     */
    public function isEmptyField(Content $content, $fieldDefIdentifier, $forcedLanguage = null)
    {
        return $this->fieldHelper->isEmptyField($content, $fieldDefIdentifier, $forcedLanguage);
    }

    /**
     * Get content name even if current user cannot access to this content
     *
     * @param $content Content|int Content or it's ID
     * @return string
     * @throws InvalidArgumentType
     */
    public function getContentName($content)
    {
        if (!$content instanceof Content) {
            // Load Content
            $content = $this->repository->sudo(
                function (Repository $repository) use ($content) {
                    /* @var $repository \Ibexa\Core\Repository\Repository */
                    return $repository->getContentService()->loadContent(intval($content));
                }
            );
        }

        if ($content instanceof Content) {
            return $this->contentExtension->getTranslatedContentName($content);
        }

        return "N/A";
    }

    /**
     * Check if current user is the anonymous user defined in ezsettings
     *
     * @return bool
     */
    public function isAnonymousUser()
    {
        // Siteaccess anonymous user ID
        $anonymousUserId = $this->configResolver->getParameter("anonymous_user_id", "ezsettings");
        // Current user ID
        $currentUserReference = $this->repository->getPermissionResolver()->getCurrentUserReference();
        if ($currentUserReference instanceof UserReference) {
            $currentUserId = $currentUserReference->getUserId();

            return $currentUserId === $anonymousUserId;
        }

        return false;
    }

    public function hasAccess(string $module, string $function): bool
    {
        return $this->authorizationChecker->isGranted(
            new Attribute($module, $function)
        );
    }

    public function getName()
    {
        return 'sqli_twig_extension';
    }
}
