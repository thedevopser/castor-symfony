<?php

namespace TheDevOpser\CastorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use TheDevOpser\CastorBundle\Command\InstallCastorCommand;

class CastorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $container->register(InstallCastorCommand::class)
            ->addTag('console.command')
            ->setAutoconfigured(true)
            ->setAutowired(true);
    }

    public function getAlias(): string
    {
        return 'castor';
    }
}
