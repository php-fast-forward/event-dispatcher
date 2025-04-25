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

        $namedEvent = $eventName ? new NamedEvent($eventName, $event) : $event;

        $listeners = new \AppendIterator();
        $listeners->append($this->listenerProvider->getListenersForEvent($event));
        $listeners->append($this->listenerProvider->getListenersForEvent($namedEvent));

        foreach ($listeners as $listener) {
            $listener($event);

            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
