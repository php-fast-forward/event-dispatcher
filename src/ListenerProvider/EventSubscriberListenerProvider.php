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

final class EventSubscriberListenerProvider implements ListenerProviderInterface
{
    private array $subscribedEvents = [];

    public function __construct(
        EventSubscriberInterface ...$eventSubscribers,
    ) {
        foreach ($eventSubscribers as $eventSubscriber) {
            $this->subscribe($eventSubscriber);
        }
    }

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
            }

            if (!\is_array($method)) {
                continue;
            }

            if (!\is_array($method[0])) {
                $this->listen($eventSubscriber, $eventName, $method[0], $method[1] ?? 0);
            }

            if (\is_array($method[0])) {
                foreach ($method as $item) {
                    $this->listen($eventSubscriber, $eventName, $item[0], $item[1] ?? 0);
                }
            }
        }
    }

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
