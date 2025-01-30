<?php

namespace TheDevOpser\CastorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('castor');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('vhost')
                    ->children()
                        ->scalarNode('url')->isRequired()->end()
                        ->scalarNode('nom')->isRequired()->end()
                        ->enumNode('server')
                            ->values(['apache2', 'nginx'])
                            ->defaultValue('apache2')
                        ->end()
                        ->enumNode('os')
                            ->values(['debian', 'ubuntu', 'rhel'])
                            ->defaultValue('debian')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}