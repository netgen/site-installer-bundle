<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteInstallerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function is_dir;

final class NetgenSiteInstallerExtension extends Extension
{
    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');

        if (is_dir($projectDir . '/ezpublish_legacy')) {
            $loader->load('services_legacy.yaml');
        } else {
            $loader->load('services.yaml');
        }
    }
}
