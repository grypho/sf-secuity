<?php

namespace Grypho\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('grypho_security');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
        ->children()
            ->arrayNode('facebook')
                ->children()
                    ->scalarNode('app_id')->end()
                    ->scalarNode('app_secret')->end()
                ->end()
            ->end()
            ->arrayNode('email')
                ->children()
                    ->scalarNode('recover_subject')->end()
                    ->scalarNode('recover_sender')->end()
                    ->scalarNode('recover_sender_email')->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
