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
use FastForward\Iterator\ClosureFactoryIteratorAggregate;
use FastForward\Iterator\UniqueIteratorIterator;
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

        $listeners = new UniqueIteratorIterator(
            new ChainIterableIterator(
                $this->listenerProvider->getListenersForEvent($event),
                $this->listenerProvider->getListenersForEvent(new NamedEvent($eventName, $event))
            )
        );

        foreach ($listeners as $listener) {
            $listener($event);

            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
