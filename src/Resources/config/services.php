<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $services
        ->defaults()
        ->autoconfigure()
        ->autowire();

    $services
        ->load('Ecourty\\McpServerBundle\\', __DIR__ . '/../../*')
        ->exclude([
            __DIR__ . '/../../DependencyInjection',
            __DIR__ . '/../../Attribute',
            __DIR__ . '/../../IO',
            __DIR__ . '/../../Enum',
            __DIR__ . '/../../Exception',
            __DIR__ . '/../../HttpFoundation',
            __DIR__ . '/../../Resources',
            __DIR__ . '/../../Tool',
            __DIR__ . '/../../McpServerBundle.php',
        ])
        ->public();
};
