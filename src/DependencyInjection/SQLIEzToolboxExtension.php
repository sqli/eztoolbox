<?php

namespace SQLI\EzToolboxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SQLIEzToolboxExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('menu.yml');

        $container->setParameter(
            'sqli_ez_toolbox.entities',
            $config['entities']
        );
        $container->setParameter(
            'sqli_ez_toolbox.admin_logger.enabled',
            $config['admin_logger']['enabled']
        );
        $container->setParameter(
            'sqli_ez_toolbox.storage_filename_cleaner.enabled',
            $config['storage_filename_cleaner']['enabled']
        );

        if ($config['storage_filename_cleaner']['enabled']) {
            $container->setParameter(
                'ezpublish.fieldType.ezimage.externalStorage.class',
                'SQLI\EzToolboxBundle\Services\Core\FieldType\ImageStorage'
            );
            $container->setParameter(
                'ezpublish.fieldType.ezmedia.externalStorage.class',
                'SQLI\EzToolboxBundle\Services\Core\FieldType\MediaStorage'
            );
            $container->setParameter(
                'ezpublish.fieldType.ezbinaryfile.externalStorage.class',
                'SQLI\EzToolboxBundle\Services\Core\FieldType\BinaryFileStorage'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $this->prependFieldType($container);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function prependFieldType(ContainerBuilder $container): void
    {
        $config = Yaml::parseFile(
            __DIR__ . '/../Resources/config/ez_field_templates.yml'
        );

        $container->prependExtensionConfig('ezpublish', $config);
    }

}
