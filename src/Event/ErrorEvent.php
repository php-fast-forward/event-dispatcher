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

namespace FastForward\EventDispatcher\Event;

use Throwable;
use Exception;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Represent a listener failure as a dispatchable event.
 *
 * The dispatcher emits this event when a listener throws while handling another event.
 */
final class ErrorEvent extends Exception implements StoppableEventInterface
{
    /**
     * Listener that raised the throwable.
     *
     * @var callable
     */
    private $listener;

    /**
     * Create an error event from a failed listener invocation.
     *
     * @param object $event original event being dispatched
     * @param callable $listener listener that raised the throwable
     * @param Throwable $throwable throwable raised by the listener
     * @param bool $propagationStopped initial propagation state for this error event
     */
    public function __construct(
        private readonly object $event,
        callable $listener,
        Throwable $throwable,
        private bool $propagationStopped = false
    ) {
        parent::__construct($throwable->getMessage(), $throwable->getCode(), $throwable);
        $this->listener = $listener;
    }

    /**
     * Determine whether propagation for this error event has been stopped.
     *
     * @return bool whether propagation is currently stopped
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Mark this error event as stopped.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Get the original event that was being dispatched.
     *
     * @return object event instance associated with the listener failure
     */
    public function getEvent(): object
    {
        return $this->event;
    }

    /**
     * Get the listener that raised the throwable.
     *
     * @return callable listener associated with this error event
     */
    public function getListener(): callable
    {
        return $this->listener;
    }

    /**
     * Get the throwable that caused this error event.
     *
     * @return Throwable throwable stored as the previous exception
     */
    public function getThrowable(): Throwable
    {
        return $this->getPrevious();
    }
}
