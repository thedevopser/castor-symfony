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
            ->scalarNode('nom')->defaultNull()->end()
            ->scalarNode('server')->defaultValue('apache2')->end()
            ->scalarNode('os')->defaultValue('debian')->end()
            ->arrayNode('ssl')
            ->canBeEnabled()
            ->children()
            ->scalarNode('certificate')->defaultNull()->end()
            ->scalarNode('certificate_key')->defaultNull()->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}