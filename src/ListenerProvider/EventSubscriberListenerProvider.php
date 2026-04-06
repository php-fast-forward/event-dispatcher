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

namespace FastForward\EventDispatcher\ListenerProvider;

use SplPriorityQueue;
use FastForward\EventDispatcher\Event\NamedEvent;
use FastForward\EventDispatcher\Exception\InvalidArgumentException;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adapt Symfony event subscribers to a PSR-14 listener provider.
 *
 * Subscribers are indexed by event name and yielded in descending priority order.
 */
final class EventSubscriberListenerProvider implements ListenerProviderInterface
{
    /**
     * Registered subscribers indexed by event name.
     *
     * @var array<string, SplPriorityQueue>
     */
    private array $subscribedEvents = [];

    /**
     * Register the initial event subscribers.
     *
     * @param EventSubscriberInterface|string ...$eventSubscribers Subscriber instances or subscriber class names.
     */
    public function __construct(EventSubscriberInterface|string ...$eventSubscribers)
    {
        foreach ($eventSubscribers as $eventSubscriber) {
            $this->addSubscriber($eventSubscriber);
        }
    }

    /**
     * Yield listeners for the provided event.
     *
     * @param object $event event instance used for listener lookup
     *
     * @return iterable<callable(object): void> listeners that accept the resolved event instance
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventName = $event instanceof NamedEvent ? $event->getName() : $event::class;

        if (! isset($this->subscribedEvents[$eventName])) {
            return [];
        }

        foreach ($this->subscribedEvents[$eventName] as [$eventSubscriber, $method]) {
            yield static function (object $event) use ($eventSubscriber, $method): void {
                $eventSubscriber->{$method}($event instanceof NamedEvent ? $event->getEvent() : $event);
            };
        }
    }

    /**
     * Register a subscriber and index its declared listeners.
     *
     * @param EventSubscriberInterface|string $eventSubscriber subscriber instance or subscriber class name
     *
     * @throws InvalidArgumentException thrown when the provided class name is not a Symfony event subscriber
     */
    public function addSubscriber(EventSubscriberInterface|string $eventSubscriber): void
    {
        if (\is_string($eventSubscriber) && ! is_subclass_of($eventSubscriber, EventSubscriberInterface::class)) {
            throw InvalidArgumentException::forInvalidEventSubscriber(
                $eventSubscriber,
                EventSubscriberInterface::class,
            );
        }

        $subscribedEvents = \call_user_func([$eventSubscriber, 'getSubscribedEvents']);

        foreach ($subscribedEvents as $eventName => $method) {
            if (\is_string($method)) {
                $this->addListener($eventSubscriber, $eventName, $method);

                continue;
            }

            if (\is_array($method) && isset($method[0]) && \is_string($method[0])) {
                $this->addListener($eventSubscriber, $eventName, $method[0], $method[1] ?? 0);

                continue;
            }

            if (\is_array($method) && \is_array($method[0])) {
                foreach ($method as $item) {
                    if (\is_array($item) && isset($item[0]) && \is_string($item[0])) {
                        $this->addListener($eventSubscriber, $eventName, $item[0], $item[1] ?? 0);
                    }
                }
            }
        }
    }

    /**
     * Attach one subscriber method to the given event name.
     *
     * @param EventSubscriberInterface $eventSubscriber subscriber instance
     * @param string $eventName event class name or string identifier
     * @param string $method subscriber method to invoke
     * @param int $priority Listener priority. Higher values are yielded first.
     */
    private function addListener(
        EventSubscriberInterface $eventSubscriber,
        string $eventName,
        string $method,
        int $priority = 0,
    ): void {
        $this->subscribedEvents[$eventName] ??= new SplPriorityQueue();
        $this->subscribedEvents[$eventName]->insert([$eventSubscriber, $method], $priority);
    }
}
