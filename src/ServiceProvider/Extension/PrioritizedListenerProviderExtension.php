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
use Phly\EventDispatcher\LazyListener;
use Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider;
use Psr\Container\ContainerInterface;

/**
 * Populate the prioritized listener provider from configured listeners.
 *
 * @internal
 */
final readonly class PrioritizedListenerProviderExtension
{
    /**
     * Register configured prioritized listeners with the provider.
     *
     * @param ContainerInterface $container container used to resolve listener services
     * @param PrioritizedListenerProvider $prioritizedListenerProvider provider to extend
     *
     * @throws RuntimeException thrown when a configured listener cannot be resolved to a callable
     */
    public function __invoke(
        ContainerInterface $container,
        PrioritizedListenerProvider $prioritizedListenerProvider,
    ): void {
        $configuredListeners = $container->get(ConfiguredListenerProviderCollection::class);

        foreach ($configuredListeners->prioritizedListeners() as $listener) {
            $prioritizedListenerProvider->listen(
                $listener->eventType,
                $this->resolveCallable($container, $listener->listener, $listener->method),
                $listener->priority,
            );
        }
    }

    /**
     * Resolve a configured prioritized listener to a callable value.
     *
     * @param ContainerInterface $container container used for service resolution
     * @param object|string|callable $listener listener value or service identifier
     * @param string|null $method listener method to call when the listener is not directly callable
     *
     * @return callable resolved callable listener
     *
     * @throws RuntimeException thrown when the listener cannot be resolved to a callable
     */
    private function resolveCallable(
        ContainerInterface $container,
        object|string|callable $listener,
        ?string $method = null,
    ): callable {
        if (\is_string($listener) && $container->has($listener)) {
            return new LazyListener($container, $listener, $method);
        }

        if (null !== $method) {
            $listener = [$listener, $method];
        }

        if (! \is_callable($listener)) {
            throw RuntimeException::forUnsupportedType($listener);
        }

        return $listener;
    }
}
