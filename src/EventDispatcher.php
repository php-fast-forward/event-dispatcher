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

namespace FastForward\EventDispatcher;

use FastForward\EventDispatcher\Event\NamedEvent;
use FastForward\Iterator\ChainIterableIterator;
use FastForward\Iterator\UniqueIteratorIterator;
use Phly\EventDispatcher\ErrorEvent;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly ListenerProviderInterface $listenerProvider
    ) {}

    public function dispatch(object $event, ?string $eventName = null): object
    {
        $stoppable = $event instanceof StoppableEventInterface;

        if ($stoppable && $event->isPropagationStopped()) {
            return $event;
        }

        $eventName ??= \get_class($event);

        $listeners = new UniqueIteratorIterator(
            new ChainIterableIterator(
                $this->listenerProvider->getListenersForEvent($event),
                $this->listenerProvider->getListenersForEvent(new NamedEvent($eventName, $event))
            )
        );

        foreach ($listeners as $listener) {
            try {
                $listener($event);
            } catch (\Throwable $throwable) {
                $this->handleCaughtThrowable($throwable, $event, $listener);
            }

            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }

    private function handleCaughtThrowable(\Throwable $throwable, object $event, callable $listener): void
    {
        if ($event instanceof ErrorEvent) {
            // Re-throw the original exception, per the spec.
            throw $event->getThrowable();
        }

        $this->dispatch(new ErrorEvent($event, $listener, $throwable));

        // Re-throw the original exception, per the spec.
        throw $throwable;
    }
}
