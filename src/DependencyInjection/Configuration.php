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
                        ->scalarNode('server')->isRequired()->end()
                        ->scalarNode('os')->isRequired()->end()
                        ->arrayNode('ssl')
                            ->children()
                                ->booleanNode('enabled')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function($v) {
                                            return filter_var($v, FILTER_VALIDATE_BOOLEAN);
                                        })
                                    ->end()
                                    ->isRequired()
                                ->end()
                                ->scalarNode('certificate')->isRequired()->end()
                                ->scalarNode('certificate_key')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}