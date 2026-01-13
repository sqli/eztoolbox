<?php

namespace SQLI\EzToolboxBundle\Services\Listener;

use Exception;
use Ibexa\Contracts\Core\Repository\Events\Content\CopyContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\DeleteContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\HideContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\PublishVersionEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\RevealContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\CopySubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\CreateLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\DeleteLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\HideLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\MoveSubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\UnhideLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\SetContentStateEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\AssignSectionToSubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\TrashEvent;
use Ibexa\Contracts\Core\Repository\Events\User\CreateUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\DeleteUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\UpdateUserEvent;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Security\UserInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SQLI\EzToolboxBundle\Services\Formatter\SqliSimpleLogFormatter;
use SQLI\EzToolboxBundle\Services\SiteAccessUtilsTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BackOfficeActionsLoggerListener implements EventSubscriberInterface
{
    use SiteAccessUtilsTrait;

    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var Repository */
    private $repository;
    /** @var Logger */
    private $logger;
    /** @var Request */
    private $request;
    /** @var bool */
    private $adminLoggerEnabled;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Repository $repository,
        $logDir,
        RequestStack $requestStack,
        $adminLoggerEnabled,
        SiteAccess $siteAccess
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->repository = $repository;
        $this->request = $requestStack->getCurrentRequest();
        $this->adminLoggerEnabled = (bool)$adminLoggerEnabled;

        // Handler and formatter
        $logHandler = new StreamHandler(
            sprintf(
                "%s/log_%s-%s.log",
                $logDir,
                $siteAccess->name,
                date("Y-m-d")
            )
        );
        $logHandler->setFormatter(new SqliSimpleLogFormatter());

        $this->logger = new Logger('Log_' . $siteAccess->name);
        $this->logger->pushHandler($logHandler);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     * The array keys are event names and the value can be:
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     * For instance:
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PublishVersionEvent::class => 'logIfPublishVersionEvent',
            CopyContentEvent::class => 'logIfCopyContentEvent',
            DeleteContentEvent::class => 'logIfDeleteContentEvent',
//            CreateTagEvent::class => 'logIfCreateTagEvent',
//            UpdateTagEvent::class => 'logIfUpdateTagEvent',
//            DeleteTagEvent::class => 'logIfDeleteTagEvent',
            MoveSubtreeEvent::class => 'logIfMoveSubtreeEvent',
            CopySubtreeEvent::class => 'logIfCopySubtreeEvent',
            CreateLocationEvent::class => 'logIfCreateLocationEvent',
            DeleteLocationEvent::class => 'logIfDeleteLocationEvent',
            HideLocationEvent::class => 'logIfVisibilityLocationEvent',
            UnhideLocationEvent::class => 'logIfVisibilityLocationEvent',
            HideContentEvent::class => 'logIfVisibilityContentEvent',
            RevealContentEvent::class => 'logIfVisibilityContentEvent',
            UpdateUserEvent::class => 'logIfUserEvent',
            CreateUserEvent::class => 'logIfUserEvent',
            DeleteUserEvent::class => 'logIfUserEvent',
            TrashEvent::class => 'logIfTrashEvent',
            AssignSectionToSubtreeEvent::class => 'logIfAssignSectionEvent',
            SetContentStateEvent::class => 'logIfSetContentStateEvent',
        ];
    }

    /**
     * @param PublishVersionEvent $event
     */
    public function logIfPublishVersionEvent(PublishVersionEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $contentId = $event->getContent()->id;
        $versionId = $event->getVersionInfo()->id;
        $this->logger->notice("Content publish :");
        $this->logUserInformations();
        try {
            $content = $this->repository->getContentService()->loadContent($contentId, [], $versionId);
            $this->logger->notice("  - content name : " . $content->getName());
        } catch (Exception $exception) {
            $this->logger->error("  - content : not found");
        }
        $this->logger->notice("  - content id : " . $contentId);
        $this->logger->notice("  - content version : " . $versionId);
    }

    /**
     * Log connected user informations
     */
    private function logUserInformations(): void
    {
        /** @var UserInterface $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $this->logger->notice("  - IP : " . implode(',', $this->request->getClientIps()));
        $this->logger->notice(
            sprintf(
                "  - user name : %s [contentId=%s]",
                $user->getUsername(),
                $user->getAPIUser()->getUserId()
            )
        );
    }

    /**
     * @param CopyContentEvent $event
     */
    public function logIfCopyContentEvent(CopyContentEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $srcContentId = $event->getContent()->id;
        $srcVersion = $event->getVersionInfo() ?? $event->getContent()->getVersionInfo();
        $srcVersionId = $srcVersion->versionNo;
        $dstParentLocationId = $event->getDestinationLocationCreateStruct()->parentLocationId;
        $this->logger->notice("Content copy :");
        $this->logUserInformations();
        try {
            $this->logger->notice("  - content name : " . $event->getContent()->getName());
        } catch (Exception $exception) {
            $this->logger->error("  - content : not found");
        }
        $this->logger->notice("  - original content id : " . $srcContentId);
        $this->logger->notice("  - original content version : " . $srcVersionId);

        try {
            $dstParentLocation = $this->repository->getLocationService()->loadLocation($dstParentLocationId);
            $this->logger->notice("  - destination parent location id : $dstParentLocationId");
            $this->logger->notice(
                "  - destination parent content name : " . $dstParentLocation->getContent()->getName()
            );
        } catch (Exception $exception) {
            $this->logger->error("  - destination parent location : not found");
        }
    }

    /**
     * @param DeleteContentEvent $event
     */
    public function logIfDeleteContentEvent(DeleteContentEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $this->logger->notice("Content delete :");
        $this->logUserInformations();
        $this->logger->notice("  - content id : " . $event->getContentInfo()->id);
        $this->logger->notice("  - content name : " . $event->getContentInfo()->name);
        $this->logger->notice("  - location ids : " . implode(',', $event->getLocations()));
    }

    /**
     * @param MoveSubtreeEvent $event
     */
    public function logIfMoveSubtreeEvent(MoveSubtreeEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $this->logger->notice("Location move :");
        $this->logUserInformations();
        $this->logger->notice("  - location id : " . $event->getLocation()->id);

        // New parent
        $this->logger->notice("  - new parent location id : " . $event->getNewParentLocation()->id);
        $this->logger->notice(
            "  - new parent content name : " . $event->getNewParentLocation()->getContent()->getName()
        );
    }

    /**
     * @param CopySubtreeEvent $event
     */
    public function logIfCopySubtreeEvent(CopySubtreeEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $this->logger->notice("Location copy :");
        $this->logUserInformations();

        // Original parent
        $this->logger->notice("  - original location id : " . $event->getSubtree()->id);
        $this->logger->notice("  - original content name : " . $event->getSubtree()->getContent()->getName());

        // New Parent
        $this->logger->notice("  - copy's parent location id : " . $event->getTargetParentLocation()->id);
        $this->logger->notice(
            "  - copy's parent content name : " . $event->getTargetParentLocation()->getContent()->getName()
        );

        // New Location
        $this->logger->notice("  - copy's location id : " . $event->getLocation()->id);
    }

    /**
     * @param CreateLocationEvent $event
     */
    public function logIfCreateLocationEvent(CreateLocationEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $this->logger->notice("Location create :");
        $this->logUserInformations();

        $this->logger->notice("  - location id : " . $event->getLocation()->id);
        $this->logger->notice("  - content id : " . $event->getContentInfo()->id);
        $this->logger->notice("  - content name : " . $event->getContentInfo()->name);

        // New Parent
        $this->logger->notice("  - new parent location id : " . $event->getLocation()->id);
        try {
            $newParentLocation = $this->repository->getLocationService()->loadLocation(
                $event->getLocation()->parentLocationId
            );
            $this->logger->notice(
                "  - new parent content name : " . $newParentLocation->getContent()->getName()
            );
        } catch (Exception $exception) {
            $this->logger->error("  - new parent content : not found");
        }
    }

    /**
     * @param DeleteLocationEvent $event
     */
    public function logIfDeleteLocationEvent(DeleteLocationEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $this->logger->notice("Location delete :");
        $this->logUserInformations();
        $this->logger->notice("  - location id : " . $event->getLocation()->id);
        $this->logger->notice("  - parent location id : " . $event->getLocation()->parentLocationId);
        $this->logger->notice("  - content id : " . $event->getLocation()->contentId);
        $this->logger->notice("  - content name : " . $event->getLocation()->getContentInfo()->name);
    }

    /**
     * @param Event $event
     */
    public function logIfVisibilityLocationEvent(Event $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $actionName = null;
        if ($event instanceof HideLocationEvent) {
            $actionName = "hide";
            $location = $event->getHiddenLocation();
        }
        if ($event instanceof UnhideLocationEvent) {
            $actionName = "unhide";
            $location = $event->getRevealedLocation();
        }
        if (
            !is_null($actionName) &&
            isset($location) &&
            $location instanceof Location
        ) {
            $this->logger->notice("Location $actionName :");
            $this->logUserInformations();
            $this->logger->notice("  - location id : " . $location->id);
            $this->logger->notice("  - parent location id : " . $location->parentLocationId);
            $this->logger->notice("  - content id : " . $location->contentId);
            $this->logger->notice("  - content name : " . $location->getContentInfo()->name);
        }
    }

    /**
     * @param Event $event
     */
    public function logIfVisibilityContentEvent(Event $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $actionName = null;
        if ($event instanceof HideContentEvent) {
            $actionName = "hide";
            $contentInfo = $event->getContentInfo();
        }
        if ($event instanceof RevealContentEvent) {
            $actionName = "unhide";
            $contentInfo = $event->getContentInfo();
        }
        if (
            !is_null($actionName) &&
            isset($contentInfo) &&
            $contentInfo instanceof ContentInfo
        ) {
            $this->logger->notice("Content $actionName :");
            $this->logUserInformations();
            $this->logger->notice("  - content id : " . $contentInfo->id);
            $this->logger->notice("  - content name : " . $contentInfo->name);
        }
    }

    /**
     * @param Event $event
     */
    public function logIfUserEvent(Event $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $actionName = null;
        $actionName = $event instanceof UpdateUserEvent ? "update" : $actionName;
        $actionName = $event instanceof CreateUserEvent ? "creation" : $actionName;
        $actionName = $event instanceof DeleteUserEvent ? "delete" : $actionName;

        if (!is_null($actionName)) {
            /** @var UpdateUserEvent|CreateUserEvent|DeleteUserEvent $event */
            $user = $event->getUser();
            $this->logger->notice("User $actionName :");
            $this->logUserInformations();
            $this->logger->notice("  - user id : " . $user->id);
            $this->logger->notice("  - user name : " . $user->getName());
        }
    }

    /**
     * @param TrashEvent $event
     */
    public function logIfTrashEvent(TrashEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $this->logger->notice("Trash :");
        $this->logUserInformations();
        $this->logger->notice("  - content id : " . $event->getLocation()->getContentInfo()->id);
        $this->logger->notice("  - content name : " . $event->getLocation()->getContentInfo()->name);
        $this->logger->notice("  - location id : " . $event->getLocation()->id);
        $this->logger->notice("  - parent location id : " . $event->getLocation()->parentLocationId);
        try {
            $location = $this->repository->getLocationService()->loadLocation(
                $event->getLocation()->parentLocationId
            );
            $this->logger->notice("  - parent content name : " . $location->getContent()->getName());
        } catch (Exception $exception) {
            $this->logger->error("  - parent content : not found");
        }
    }

    /**
     * @param AssignSectionToSubtreeEvent $event
     */
    public function logIfAssignSectionEvent(AssignSectionToSubtreeEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        $this->logger->notice("Assign section :");
        $this->logUserInformations();
        $this->logger->notice("  - location id : " . $event->getLocation()->id);
        $this->logger->notice("  - location name : " . $event->getLocation()->getContentInfo()->name);
        $this->logger->notice("  - section id : " . $event->getSection()->identifier);
        $this->logger->notice("  - section name : " . $event->getSection()->name);
    }

    /**
     * @param SetContentStateEvent $event
     */
    public function logIfSetContentStateEvent(SetContentStateEvent $event)
    {
        // Log only for admin siteaccesses
        if (!$this->adminLoggerEnabled || !$this->isAdminSiteAccess()) {
            return;
        }

        if ($event instanceof SetContentStateEvent) {
            $this->logger->notice("Change object state :");
            $this->logUserInformations();
            $this->logger->notice("  - content id : " . $event->getContentInfo()->id);
            // Content
            $this->logger->notice("  - content name : " . $event->getContentInfo()->name);
            // Object state group
            $this->logger->notice("  - object state group name : " . $event->getObjectStateGroup()->getName());
            // Object state
            $this->logger->notice("  - object state name : " . $event->getObjectState()->getName());
        }
    }
}
