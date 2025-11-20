<?php

namespace SQLI\EzToolboxBundle\Services;

use Ibexa\Bundle\AdminUi\IbexaAdminUiBundle;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;

trait SiteAccessUtilsTrait
{
    protected SiteAccessService $siteAccessService;

    /**
     * autowiring
     *
     * @required
     *
     * @param SiteAccessService $siteAccessService
     */
    public function setSiteAccessSettings(SiteAccessService $siteAccessService): void
    {
        $this->siteAccessService = $siteAccessService;
    }

    /**
     * Check if specified (or current if null) siteaccess name is in admin group
     *
     * @param string|null $siteAccessName
     *
     * @return bool
     * @throws NotFoundException
     */
    public function isAdminSiteAccess(?string $siteAccessName = null): bool
    {
        if (is_null($siteAccessName)) {
            $siteAccessName = $this->getSiteAccessName();
        }

        $siteaccess = $this->siteAccessService->get($siteAccessName);
        foreach ($siteaccess->groups as $siteAccessGroup) {
            if ($siteAccessGroup->getName() === IbexaAdminUiBundle::ADMIN_GROUP_NAME) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSiteAccessName(): string
    {
        return $this->siteAccessService->getCurrent()->name;
    }
}
