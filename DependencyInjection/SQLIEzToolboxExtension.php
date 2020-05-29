<?php

namespace SQLI\EzToolboxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SQLIEzToolboxExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration( $configuration, $configs );

        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
        $loader->load( 'services.yml' );
        $loader->load( 'menu.yml' );

        $container->setParameter( 'sqli_ez_toolbox.entities',
                                  $config['entities'] );
        $container->setParameter( 'sqli_ez_toolbox.contenttype_installer.installation_directory',
                                  $config['contenttype_installer']['installation_directory'] );
        $container->setParameter( 'sqli_ez_toolbox.contenttype_installer.is_absolute_path',
                                  $config['contenttype_installer']['is_absolute_path'] );
    }
}
