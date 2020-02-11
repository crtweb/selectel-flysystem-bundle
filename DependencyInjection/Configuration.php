<?php

declare(strict_types=1);

namespace Creative\SelectelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Класс с описанием настроек бандла.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     *
     * @psalm-suppress UndefinedMethod
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('creative_selectel');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('account_id')->defaultValue('')->end()
                ->scalarNode('client_id')->defaultValue('')->end()
                ->scalarNode('client_password')->defaultValue('')->end()
                ->scalarNode('container')->defaultValue('')->end()
                ->scalarNode('api_host')->defaultValue('https://api.selcdn.ru')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
