<?php

declare(strict_types=1);

namespace Ecourty\McpServerBundle\DependencyInjection;

use Ecourty\McpServerBundle\MethodHandler\InitializeMethodHandler;
use Ecourty\McpServerBundle\Service\ServerConfigurationRegistry;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * This class loads and manages the bundle configuration.
 */
class McpServerBundleExtension extends Extension
{
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    public function getAlias(): string
    {
        return 'mcp_server';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var Configuration $configuration */
        $configuration = $this->getConfiguration($configs, $container);

        /**
         * @var array{server?: array{name: string, title?: string, version: string}, servers?: array<string, array{name: string, title?: string, version: string}>} $config
         */
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        // Transform configuration for ServerConfigurationRegistry
        // After configuration processing, we always have a 'servers' array
        $serverConfigurations = $config['servers'] ?? [];

        // Register the ServerConfigurationRegistry with the server configurations
        $serverConfigRegistryDefinition = new Definition(ServerConfigurationRegistry::class, [$serverConfigurations]);
        $container->setDefinition(ServerConfigurationRegistry::class, $serverConfigRegistryDefinition);

        // Store server configurations as a container parameter for compile-time validation
        $container->setParameter('mcp_server.configured_servers', array_keys($serverConfigurations));

        // Configure the InitializeMethodHandler to use the ServerConfigurationRegistry
        // since we now always have multiple servers (even if just one)
        $container->getDefinition(InitializeMethodHandler::class)
            ->setArgument('$serverConfigurationRegistry', $container->getDefinition(ServerConfigurationRegistry::class))
            ->setArgument('$serverName', null)
            ->setArgument('$serverTitle', null)
            ->setArgument('$serverVersion', null);
    }
}
