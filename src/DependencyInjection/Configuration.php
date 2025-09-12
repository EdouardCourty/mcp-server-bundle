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
    public const string DEFAULT_NAME = 'MCP Server';
    public const string DEFAULT_TITLE = self::DEFAULT_NAME;
    public const string DEFAULT_VERSION = '1.0.0';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mcp_server');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode // @phpstan-ignore method.notFound
            ->validate()
                ->ifTrue(function ($config) {
                    // Check that at least one of 'server' or 'servers' is defined
                    $hasServer = isset($config['server']) && !empty($config['server']);
                    $hasServers = isset($config['servers']) && !empty($config['servers']);

                    return !$hasServer && !$hasServers;
                })
                ->then(function ($config) {
                    // Default to single server configuration
                    $config['server'] = [
                        'name' => self::DEFAULT_NAME,
                        'title' => self::DEFAULT_TITLE,
                        'version' => self::DEFAULT_VERSION,
                    ];

                    return $config;
                })
            ->end()
            ->validate()
                ->always(function (array $config): array {
                    // Automatically merge single server config into servers config
                    $hasServer = isset($config['server']) && !empty($config['server']);
                    $hasServers = isset($config['servers']) && !empty($config['servers']);

                    if ($hasServer && $hasServers) {
                        // Both are defined - merge server into servers as 'default' if not already present
                        if (!isset($config['servers']['default'])) {
                            $config['servers']['default'] = $config['server'];
                        }
                        // Remove the single server config since it's now in servers
                        unset($config['server']);
                    } elseif ($hasServer) {
                        // Only single server or no servers - convert to servers config
                        $config['servers'] = [
                            'default' => $config['server'],
                        ];
                        unset($config['server']);
                    }
                    // If only servers config exists, leave it as-is

                    return $config;
                })
            ->end()
            ->children()
                ->arrayNode('server')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('name')->defaultValue(self::DEFAULT_NAME)->end()
                        ->scalarNode('title')->defaultValue(self::DEFAULT_TITLE)->end()
                        ->scalarNode('version')->defaultValue(self::DEFAULT_VERSION)->end()
                    ->end()
                ->end()
                ->arrayNode('servers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('title')->end()
                            ->scalarNode('version')->defaultValue(self::DEFAULT_VERSION)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
