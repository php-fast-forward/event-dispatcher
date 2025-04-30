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

/**
 * Class EventDispatcher.
 *
 * PSR-14 compatible event dispatcher that allows for both named and unnamed event dispatching.
 * This class MUST handle event propagation and listener invocation in accordance with PSR-14.
 *
 * Listeners are retrieved for both the standard event and the NamedEvent form.
 * Implementations using this class SHOULD register both types to ensure complete propagation coverage.
 *
 * @package FastForward\EventDispatcher
 */
final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var ListenerProviderInterface the provider instance responsible for resolving listeners
     */
    private readonly ListenerProviderInterface $listenerProvider;

    /**
     * Constructs the EventDispatcher.
     *
     * The constructor MUST receive a ListenerProviderInterface that supplies event listeners
     * based on the event instance or a named wrapper of that event.
     *
     * @param ListenerProviderInterface $listenerProvider provides event listeners for events
     */
    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * Dispatches the given event to all applicable listeners.
     *
     * If the event implements StoppableEventInterface and propagation has already been stopped,
     * the event SHALL be returned immediately without invoking any listeners.
     *
     * If a listener throws an exception, it SHALL be handled by dispatching an ErrorEvent
     * unless the event is already an ErrorEvent, in which case the original exception MUST be rethrown.
     *
     * @param object      $event     the event to dispatch
     * @param null|string $eventName optional name to explicitly identify the event
     *
     * @return object the event after being handled by listeners, potentially modified
     *
     * @throws \Throwable any uncaught exception thrown by a listener that is not handled
     */
    public function dispatch(object $event, ?string $eventName = null): object
    {
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        $listeners = new UniqueIteratorIterator(
            new ChainIterableIterator(
                $this->listenerProvider->getListenersForEvent($event),
                $this->listenerProvider->getListenersForEvent(new NamedEvent($event, $eventName))
            )
        );

        foreach ($listeners as $listener) {
            try {
                $listener($event);
            } catch (\Throwable $throwable) {
                $this->handleThrowable($throwable, $event, $listener);
            }

            if (
                $event instanceof StoppableEventInterface
                && $event->isPropagationStopped()
            ) {
                return $event;
            }
        }

        return $event;
    }

    /**
     * Handles any exceptions thrown by listeners during dispatch.
     *
     * If the current event is an ErrorEvent, the exception MUST be rethrown directly.
     * Otherwise, a new ErrorEvent instance SHALL be dispatched and the original exception rethrown.
     *
     * @param \Throwable $throwable the thrown exception
     * @param object     $event     the event being dispatched when the exception occurred
     * @param callable   $listener  the listener that threw the exception
     *
     * @throws \Throwable always rethrows the original exception if no alternative handling is performed
     */
    private function handleThrowable(\Throwable $throwable, object $event, callable $listener): void
    {
        if ($event instanceof ErrorEvent) {
            // MUST rethrow the original exception as per PSR-14 spec.
            throw $event->getThrowable();
        }

        $this->dispatch(new ErrorEvent($event, $listener, $throwable));

        // Rethrow the original exception if not handled.
        throw $throwable;
    }
}
