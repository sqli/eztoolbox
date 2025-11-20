<?php

namespace SQLI\EzToolboxBundle\DependencyInjection\Compiler;

use SQLI\EzToolboxBundle\Services\Parameter\ParameterHandlerRepository;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ParameterHandlerTagCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ParameterHandlerRepository::class)) {
            return;
        }

        $definition = $container->findDefinition(ParameterHandlerRepository::class);

        // Search tagged services
        $taggedServices = $container->findTaggedServiceIds('sqli.parameter_handler');
        foreach ($taggedServices as $id => $taggedService) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}
