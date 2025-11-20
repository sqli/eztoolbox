<?php

namespace SQLI\EzToolboxBundle\DependencyInjection\PolicyProvider;

use Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

class SQLIEzToolboxPolicyProvider extends YamlPolicyProvider
{
    /**
     * Returns an array of files where the policy configuration lies.
     * Each file path MUST be absolute.
     *
     * @return array
     */
    public function getFiles(): array
    {
        return array(
            __DIR__ . '/../../Resources/config/policies.yml',
        );
    }
}
