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
            $config = $this->processConfiguration($configuration, $configs);

            $container->setParameter('castor.vhost.url', $config['vhost']['url']);
            $container->setParameter('castor.vhost.nom', $config['vhost']['nom']);
            $container->setParameter('castor.vhost.server', $config['vhost']['server']);
            $container->setParameter('castor.vhost.os', $config['vhost']['os']);
            $container->setParameter('castor.vhost.ssl.enabled', $config['vhost']['ssl']['enabled']);
            $container->setParameter('castor.vhost.ssl.certificate', $config['vhost']['ssl']['certificate']);
            $container->setParameter('castor.vhost.ssl.certificate_key', $config['vhost']['ssl']['certificate_key']);

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