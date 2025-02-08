<?php

        namespace TheDevOpser\CastorBundle\DependencyInjection;

        use Symfony\Component\DependencyInjection\ContainerBuilder;
        use Symfony\Component\HttpKernel\DependencyInjection\Extension;
        use TheDevOpser\CastorBundle\Command\InstallCastorCommand;
        use Symfony\Component\Yaml\Yaml;

        class CastorExtension extends Extension
        {
            public function load(array $configs, ContainerBuilder $container): void
            {

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