<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/event-dispatcher.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @link      https://github.com/php-fast-forward/event-dispatcher
 * @copyright Copyright (c) 2025 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\EventDispatcher\ListenerProvider;

use FastForward\EventDispatcher\Event\NamedEvent;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriberListenerProvider.
 *
 * Provides event listeners by interpreting Symfony-style EventSubscriberInterface implementations.
 *
 * This class MUST be used when event subscribers are registered that implement the
 * getSubscribedEvents() method. It SHALL resolve listeners dynamically for each event
 * dispatched, including support for NamedEvent wrappers.
 *
 * Priority is handled using a SplPriorityQueue. Listeners with higher priority values
 * are returned earlier in the iterable.
 *
 * This class supports both individual and multiple listener declarations for each event name.
 *
 * @package FastForward\EventDispatcher\ListenerProvider
 */
final class EventSubscriberListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<string, \SplPriorityQueue> Stores event names mapped to a priority queue of listeners.
     *                                       Keys MUST be event class names or string identifiers.
     */
    private array $subscribedEvents = [];

    /**
     * Constructs the listener provider with one or more event subscribers.
     *
     * @param EventSubscriberInterface|string ...$eventSubscribers One or more event subscriber instances.
     */
    public function __construct(
        EventSubscriberInterface|string ...$eventSubscribers,
    ) {
        foreach ($eventSubscribers as $eventSubscriber) {
            $this->subscribe($eventSubscriber);
        }
    }

    /**
     * Returns an iterable of listeners for the given event.
     *
     * If the event is a NamedEvent, the name is extracted and used for lookup.
     * Otherwise, the class name of the event is used.
     *
     * @param object $event the event instance for which to retrieve listeners
     *
     * @return iterable<callable> a list of listeners for the event
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventName = $event instanceof NamedEvent ? $event->getName() : \get_class($event);

        if (!isset($this->subscribedEvents[$eventName])) {
            return [];
        }

        foreach ($this->subscribedEvents[$eventName] as [$eventSubscriber, $method]) {
            yield static function (object $event) use ($eventSubscriber, $method): void {
                $eventSubscriber->{$method}($event instanceof NamedEvent ? $event->getEvent() : $event);
            };
        }
    }

    /**
     * Registers an event subscriber and its declared listeners.
     *
     * This method SHALL interpret the structure returned by getSubscribedEvents().
     * It supports:
     * - Single method name as string
     * - Array with method and priority
     * - Array of multiple method-priority pairs
     *
     * @param EventSubscriberInterface|string $eventSubscriber the subscriber instance or class name
     *
     * @throws \InvalidArgumentException if a string is provided that does not implement the required interface
     */
    private function subscribe(EventSubscriberInterface|string $eventSubscriber): void
    {
        if (\is_string($eventSubscriber) && !is_subclass_of($eventSubscriber, EventSubscriberInterface::class)) {
            throw new \InvalidArgumentException(\sprintf(
                'Event subscriber "%s" must implement "%s"',
                $eventSubscriber,
                EventSubscriberInterface::class,
            ));
        }

        $subscribedEvents = \call_user_func([$eventSubscriber, 'getSubscribedEvents']);

        foreach ($subscribedEvents as $eventName => $method) {
            if (\is_string($method)) {
                $this->listen($eventSubscriber, $eventName, $method);

                continue;
            }

            if (\is_array($method) && isset($method[0]) && \is_string($method[0])) {
                $this->listen($eventSubscriber, $eventName, $method[0], $method[1] ?? 0);

                continue;
            }

            if (\is_array($method) && \is_array($method[0])) {
                foreach ($method as $item) {
                    if (\is_array($item) && isset($item[0]) && \is_string($item[0])) {
                        $this->listen($eventSubscriber, $eventName, $item[0], $item[1] ?? 0);
                    }
                }
            }
        }
    }

    /**
     * Attaches a specific method of an event subscriber to a named event.
     *
     * Listeners are stored in a priority queue, and higher priority values are returned earlier.
     *
     * @param EventSubscriberInterface $eventSubscriber the subscriber instance
     * @param string                   $eventName       the name or class of the event to listen for
     * @param string                   $method          the method on the subscriber to call
     * @param int                      $priority        Optional listener priority. Higher numbers are executed earlier.
     */
    private function listen(
        EventSubscriberInterface $eventSubscriber,
        string $eventName,
        string $method,
        int $priority = 0,
    ): void {
        $this->subscribedEvents[$eventName] ??= new \SplPriorityQueue();
        $this->subscribedEvents[$eventName]->insert([$eventSubscriber, $method], $priority);
    }
}
