<?php

namespace SQLI\EzToolboxBundle;

use SQLI\EzToolboxBundle\DependencyInjection\Compiler\ParameterHandlerTagCompilerPass;
use SQLI\EzToolboxBundle\DependencyInjection\PolicyProvider\SQLIEzToolboxPolicyProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SQLIEzToolboxBundle extends Bundle
{
    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $eZExtension = $container->getExtension('ibexa');
        $eZExtension->addPolicyProvider(new SQLIEzToolboxPolicyProvider());
        $container->addCompilerPass(new ParameterHandlerTagCompilerPass());
    }
}
