<?php

namespace SQLI\EzToolboxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sqli_ez_toolbox');
        $rootNode = $treeBuilder->getRootNode();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
            ->arrayNode('entities')
            ->prototype('array')
            ->children()
            ->scalarNode('directory')->isRequired()->end()
            ->scalarNode('namespace')->defaultNull()->end()
            ->end()
            ->end()
            ->end() // entities
            ->arrayNode('admin_logger')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('enabled')->isRequired()->defaultFalse()->end()
            ->end()
            ->end() // admin logger
            ->arrayNode('storage_filename_cleaner')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('enabled')->isRequired()->defaultFalse()->end()
            ->end()
            ->end() // end storage_filename_cleaner
            ->end();

        return $treeBuilder;
    }
}
