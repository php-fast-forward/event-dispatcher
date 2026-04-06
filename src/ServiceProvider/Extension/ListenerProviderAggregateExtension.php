<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/event-dispatcher.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2025-2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/event-dispatcher
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\EventDispatcher\ServiceProvider\Extension;

use FastForward\EventDispatcher\Exception\RuntimeException;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Attach configured listener providers to the aggregate provider.
 *
 * @internal
 */
final readonly class ListenerProviderAggregateExtension
{
    /**
     * Attach configured listener providers to the aggregate.
     *
     * @param ContainerInterface $container container used to resolve provider services
     * @param ListenerProviderAggregate $listenerProviderAggregate aggregate provider to extend
     *
     * @throws RuntimeException thrown when a configured provider cannot be resolved
     */
    public function __invoke(
        ContainerInterface $container,
        ListenerProviderAggregate $listenerProviderAggregate,
    ): void {
        $configuredListeners = $container->get(ConfiguredListenerProviderCollection::class);

        foreach ($configuredListeners->listenerProviders() as $listenerProvider) {
            $listenerProviderAggregate->attach($this->resolveListenerProvider($container, $listenerProvider));
        }
    }

    /**
     * Resolve one configured listener provider to a concrete provider instance.
     *
     * @param ContainerInterface $container container used for service resolution
     * @param ListenerProviderInterface|string $listenerProvider provider instance or service identifier
     *
     * @return ListenerProviderInterface resolved listener provider
     *
     * @throws RuntimeException thrown when the resolved value is not a listener provider
     */
    private function resolveListenerProvider(
        ContainerInterface $container,
        ListenerProviderInterface|string $listenerProvider,
    ): ListenerProviderInterface {
        if (\is_string($listenerProvider) && $container->has($listenerProvider)) {
            $listenerProvider = $container->get($listenerProvider);
        }

        if (! $listenerProvider instanceof ListenerProviderInterface) {
            throw RuntimeException::forUnsupportedType($listenerProvider);
        }

        return $listenerProvider;
    }
}
