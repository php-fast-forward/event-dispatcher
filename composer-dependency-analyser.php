<?php

declare(strict_types=1);

use FastForward\DevTools\Config\ComposerDependencyAnalyserConfig;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return ComposerDependencyAnalyserConfig::configure(
    static function (Configuration $configuration): void {
        // Symfony subscribers and attributes are optional integrations. Consumers install
        // symfony/event-dispatcher explicitly; keep this exception limited to their adapters.
        $configuration->ignoreErrorsOnPackageAndPaths(
            'symfony/event-dispatcher',
            [
                __DIR__ . '/src/ListenerProvider/EventSubscriberListenerProvider.php',
                __DIR__ . '/src/ServiceProvider/Configuration/ConfiguredListenerProviderCollection.php',
                __DIR__ . '/src/ServiceProvider/Extension/EventSubscriberListenerProviderExtension.php',
            ],
            [ErrorType::DEV_DEPENDENCY_IN_PROD],
        );
    },
);
