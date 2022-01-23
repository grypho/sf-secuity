<?php

namespace Grypho\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GryphoSecurityExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    // note that this method is called loadInternal and not load
    // changes here require a cache flush (even in debug)
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        if (isset($mergedConfig['facebook'])) {
            $container->setParameter('facebook', $mergedConfig['facebook']);
        }
        if (isset($mergedConfig['email'])) {
            $container->setParameter('gsb_email', $mergedConfig['email']);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
    }
}
