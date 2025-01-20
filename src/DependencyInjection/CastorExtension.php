<?php

namespace TheDevOpser\CastorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class CastorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $container->autowire(Command\InstallCastorCommand::class)
            ->addTag('console.command')
            ->setPublic(false);
    }

    public function getAlias(): string
    {
        return 'castor';
    }
}