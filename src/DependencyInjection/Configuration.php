<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for the MCP Server bundle.
 */
class Configuration implements ConfigurationInterface
{
    private const string DEFAULT_NAME = 'MCP Server';
    private const string DEFAULT_VERSION = '1.0.0';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mcp_server');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode // @phpstan-ignore method.notFound
            ->children()
                ->arrayNode('server')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->defaultValue(self::DEFAULT_NAME)->end()
                        ->scalarNode('version')->defaultValue(self::DEFAULT_VERSION)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
