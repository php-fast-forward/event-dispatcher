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
use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Populate the subscriber-based listener provider from configured listeners.
 *
 * @internal
 */
final readonly class EventSubscriberListenerProviderExtension
{
    /**
     * Add configured event subscribers to the provider instance.
     *
     * @param ContainerInterface $container container used to resolve subscriber services
     * @param EventSubscriberListenerProvider $eventSubscriberListenerProvider provider to extend
     *
     * @throws RuntimeException thrown when a configured subscriber cannot be resolved
     */
    public function __invoke(
        ContainerInterface $container,
        EventSubscriberListenerProvider $eventSubscriberListenerProvider,
    ): void {
        $configuredListeners = $container->get(ConfiguredListenerProviderCollection::class);

        foreach ($configuredListeners->eventSubscribers() as $eventSubscriber) {
            $eventSubscriberListenerProvider->addSubscriber(
                $this->resolveEventSubscriber($container, $eventSubscriber)
            );
        }
    }

    /**
     * Resolve one configured subscriber to a concrete subscriber instance.
     *
     * @param ContainerInterface $container container used for service resolution
     * @param EventSubscriberInterface|string $eventSubscriber subscriber instance or service identifier
     *
     * @return EventSubscriberInterface resolved subscriber instance
     *
     * @throws RuntimeException thrown when the resolved value is not an event subscriber
     */
    private function resolveEventSubscriber(
        ContainerInterface $container,
        EventSubscriberInterface|string $eventSubscriber,
    ): EventSubscriberInterface {
        if (\is_string($eventSubscriber) && $container->has($eventSubscriber)) {
            $eventSubscriber = $container->get($eventSubscriber);
        }

        if (! $eventSubscriber instanceof EventSubscriberInterface) {
            throw RuntimeException::forUnsupportedType($eventSubscriber);
        }

        return $eventSubscriber;
    }
}
