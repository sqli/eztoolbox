<?php

namespace SQLI\EzToolboxBundle\Menu;

use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use ReflectionException;
use SQLI\EzToolboxBundle\Services\TabEntityHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuListener implements EventSubscriberInterface
{
    public const SQLI_ADMIN_MENU_ROOT = "sqli_admin__menu_root";
    public const SQLI_ADMIN_MENU_ENTITIES_TAB_PREFIX = "sqli_admin__menu_entities_tab__";
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;
    /** @var TabEntityHelper */
    private $tabEntityHelper;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TabEntityHelper $tabEntityHelper
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tabEntityHelper = $tabEntityHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => ['onMainMenuConfigure', 0],
        ];
    }

    /**
     * @param ConfigureMenuEvent $event
     * @throws ReflectionException
     */
    public function onMainMenuConfigure(ConfigureMenuEvent $event): void
    {
        $rootMenu = $event->getMenu()->getRoot();
        if ($this->authorizationChecker->isGranted(new Attribute('sqli_admin','list_entities'))) {
            $customMenuItem = $rootMenu->addChild(self::SQLI_ADMIN_MENU_ROOT,[
                'attributes' => [
                    'data-tooltip-placement' => 'right',
                    'data-tooltip-extra-class' => 'ibexa-tooltip--info-neon',
                ],
                'extras' => [
                    'icon' => 'view-list',
                    'orderNumber' => 90,
                ],
            ])->setExtra('translation_domain', 'sqli_admin');
            // Read "tabname" entity's annotations to generate submenu items
            $tabClasses = $this->tabEntityHelper->entitiesGroupedByTab();
            foreach (array_keys($tabClasses) as $tabname) {
                $customMenuItem->addChild(
                    self::SQLI_ADMIN_MENU_ENTITIES_TAB_PREFIX . $tabname,
                    [
                        'label' => self::SQLI_ADMIN_MENU_ENTITIES_TAB_PREFIX . $tabname,
                        'route' => 'sqli_eztoolbox_entitymanager_homepage',
                        'routeParameters' => ['tabname' => $tabname],
                    ]
                )->setExtra('translation_domain', 'sqli_admin');
            }
        }
    }
}
