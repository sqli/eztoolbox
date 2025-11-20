<?php

/**
 * KnpMenuBundle Menu Builder service implementation for AdminUI Section Edit contextual sidebar menu.
 *
 * @see https://symfony.com/doc/current/bundles/KnpMenuBundle/menu_builder_service.html
 */

namespace SQLI\EzToolboxBundle\Menu;

use Ibexa\Contracts\AdminUi\Menu\AbstractBuilder;
use Ibexa\AdminUi\Menu\MenuItemFactory;
use InvalidArgumentException;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditElementRightSidebarBuilder extends AbstractBuilder
{
    /* Menu items */
    public const ITEM__EDIT = 'element__sidebar_right__edit';
    public const ITEM__SAVE = 'edit_element__sidebar_right__save';
    public const ITEM__CANCEL = 'edit_element__sidebar_right__cancel';

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(
        MenuItemFactory $factory,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator
    ) {
        parent::__construct($factory, $eventDispatcher);
        $this->translator = $translator;
    }

    /**
     * @param array $options
     * @return ItemInterface
     * @throws InvalidArgumentException
     */
    public function createStructure(array $options): ItemInterface
    {
        /** @var ItemInterface|ItemInterface[] $menu */
        $menu = $this->factory->createItem('root');

        if (isset($options['edit_button_name'])) {
            $menu->setChildren(
                [
                    self::ITEM__EDIT => $this->createMenuItem(
                        self::ITEM__EDIT,
                        [
                            'uri' => $options['edit_button_name'],
                            'label' => $this->translator->trans(self::ITEM__EDIT, [], 'sqli_admin'),
                            'extras' => ['icon' => 'edit'],
                        ]
                    )
                ]
            );
        }

        if (isset($options['save_button_name'])) {
            $menu->setChildren([
            self::ITEM__SAVE => $this->createMenuItem(
                self::ITEM__SAVE,
                [
                    'attributes' => [
                        'class' => 'ibexa-btn--trigger',
                        'data-click' => sprintf('#%s', $options['save_button_name']),
                    ],
                    'label' => $this->translator->trans(self::ITEM__SAVE, [], 'sqli_admin'),
                    'extras' => ['icon' => 'save'],
                ]
            )
                ]);
        }

        $menu->addChild(
            $this->createMenuItem(
                self::ITEM__CANCEL,
                [
                    'uri' => $options['cancel_url'],
                    'label' => $this->translator->trans(self::ITEM__CANCEL, [], 'sqli_admin'),
                    'extras' => ['icon' => 'circle-close'],
                ]
            )
        );

        return $menu;
    }

    /**
     * @return string
     */
    protected function getConfigureEventName(): string
    {
        return "sqli_eztoolbox.admin.edit_element.sidebar_right";
    }
}
