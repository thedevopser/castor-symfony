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
                $configuration = new Configuration();
                $configFile = $this->getConfigurationFilePath($container);

                if (!file_exists($configFile)) {
                    $this->createDefaultConfiguration($configFile);
                }

                $fileConfig = Yaml::parseFile($configFile);
                if (isset($fileConfig['castor'])) {
                    $configs = array_merge($configs, [$fileConfig['castor']]);
                }

                $config = $this->processConfiguration($configuration, $configs);

                if (!isset($config['vhost'])) {
                    throw new \RuntimeException('La configuration vhost est manquante dans castor.yaml');
                }

                $container->setParameter('vhost.url', $config['vhost']['url']);
                $container->setParameter('vhost.nom', $config['vhost']['nom']);
                $container->setParameter('vhost.server', $config['vhost']['server']);
                $container->setParameter('vhost.os', $config['vhost']['os']);
                $container->setParameter('vhost.ssl.enabled', $config['vhost']['ssl']['enabled']);
                $container->setParameter('vhost.ssl.certificate', $config['vhost']['ssl']['certificate']);
                $container->setParameter('vhost.ssl.certificate_key', $config['vhost']['ssl']['certificate_key']);

                $container->register(InstallCastorCommand::class)
                    ->addTag('console.command')
                    ->setAutoconfigured(true)
                    ->setAutowired(true);
            }

            private function getConfigurationFilePath(ContainerBuilder $container): string
            {
                return $container->getParameter('kernel.project_dir') . '/config/packages/castor.yaml';
            }

            private function createDefaultConfiguration(string $configFile): void
            {
                $defaultConfig = <<<YAML
        castor:
          vhost:
            url: "%env(CASTOR_VHOST_URL)%"
            nom: "%env(CASTOR_VHOST_NOM)%"
            server: "%env(CASTOR_VHOST_SERVER)%"
            os: "%env(CASTOR_VHOST_OS)%"
            ssl:
              enabled: "%env(bool:CASTOR_VHOST_SSL_ENABLE)%"
              certificate: "%env(CASTOR_VHOST_SSL_CERTIFICATE)%"
              certificate_key: "%env(CASTOR_VHOST_SSL_CERTIFICATE_KEY)%"
        YAML;

                file_put_contents($configFile, $defaultConfig);
            }

            public function getAlias(): string
            {
                return 'castor';
            }
        }