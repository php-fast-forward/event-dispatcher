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

namespace FastForward\EventDispatcher;

use FastForward\EventDispatcher\Event\NamedEvent;
use Throwable;
use FastForward\EventDispatcher\Event\ErrorEvent;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyContractsEventDispatcherInterface;

/**
 * Dispatch events through PSR-14 listener providers and optional named wrappers.
 *
 * This dispatcher resolves listeners for the original event object first. When the event is not already
 * a {@see NamedEvent}, it dispatches a named wrapper afterward so listeners registered by string
 * identifier can observe the same event instance.
 *
 * @see \Psr\EventDispatcher\EventDispatcherInterface
 */
final readonly class EventDispatcher implements SymfonyContractsEventDispatcherInterface
{
    /**
     * Create a dispatcher backed by the provided listener provider.
     *
     * @param ListenerProviderInterface $listenerProvider listener provider used to resolve event listeners
     */
    public function __construct(
        private ListenerProviderInterface $listenerProvider
    ) {}

    /**
     * Dispatch an event to all matching listeners.
     *
     * Stoppable events are returned immediately when propagation has already been halted. When a listener
     * throws during a non-error dispatch, the dispatcher emits an {@see ErrorEvent} before rethrowing the
     * original throwable.
     *
     * @param object $event event object to dispatch
     * @param string|null $eventName explicit name for the generated {@see NamedEvent} wrapper
     *
     * @return object the dispatched event instance
     *
     * @throws Throwable thrown when a listener failure is not absorbed by an error-event listener
     */
    public function dispatch(object $event, ?string $eventName = null): object
    {
        $listeners = $this->listenerProvider->getListenersForEvent($event);

        $this->callListeners($listeners, $event);

        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        // Attempt to dispatch a NamedEvent wrapper if the original event is not already one.
        // This allows listeners registered for the named form to be invoked as well.
        if ($event instanceof NamedEvent) {
            return $event->getEvent();
        }

        return $this->dispatch(new NamedEvent($event, $eventName ?? $event::class));
    }

    /**
     * Invoke the provided listeners until propagation stops or a listener fails.
     *
     * @param iterable<callable> $listeners listeners resolved for the current event
     * @param object $event event instance passed to each listener
     *
     * @throws Throwable thrown when listener failure handling rethrows an error
     */
    private function callListeners(iterable $listeners, object $event): void
    {
        $stoppable = $event instanceof StoppableEventInterface;

        foreach ($listeners as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }

            try {
                $listener($event);
            } catch (Throwable $throwable) {
                $this->handleThrowable($throwable, $event, $listener);
            }
        }
    }

    /**
     * Dispatch an error event for a listener failure and rethrow the original throwable.
     *
     * Error events are not wrapped again, which prevents recursive error dispatch when an error listener
     * itself fails.
     *
     * @param Throwable $throwable throwable raised by the listener
     * @param object $event event being dispatched when the failure occurred
     * @param callable $listener listener that raised the throwable
     *
     * @throws Throwable always rethrows after attempting error dispatch
     */
    private function handleThrowable(Throwable $throwable, object $event, callable $listener): void
    {
        if ($event instanceof ErrorEvent) {
            // Rethrow the original exception to avoid recursive error dispatch.
            throw $event->getThrowable();
        }

        $this->dispatch(new ErrorEvent($event, $listener, $throwable));

        // Rethrow the original exception if not handled.
        throw $throwable;
    }
}
