<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteInstallerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class NetgenSiteInstallerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        if (is_dir($container->getParameter('kernel.project_dir') . '/vendor/netgen/media-site-legacy-data')) {
            $loader->load('services_legacy.yaml');
        } else {
            $loader->load('services.yaml');
        }
    }
}
