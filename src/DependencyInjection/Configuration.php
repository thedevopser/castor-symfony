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

        return $treeBuilder;
    }
}