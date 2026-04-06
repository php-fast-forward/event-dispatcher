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

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Wrap an event object with an explicit dispatch name.
 *
 * The name defaults to the wrapped event class when no explicit identifier is provided.
 */
final readonly class NamedEvent implements StoppableEventInterface
{
    /**
     * Dispatch name associated with the wrapped event.
     */
    private string $name;

    /**
     * Create a named wrapper for the provided event.
     *
     * @param object $event original event instance
     * @param string|null $name Explicit dispatch name. Defaults to the wrapped event class name.
     */
    public function __construct(
        private object $event,
        ?string $name = null
    ) {
        $this->name = $name ?? $this->event::class;
    }

    /**
     * Get the dispatch name for the wrapped event.
     *
     * @return string event name used for listener lookup
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the original event instance.
     *
     * @return object wrapped event instance
     */
    public function getEvent(): object
    {
        return $this->event;
    }

    /**
     * Determine whether propagation has been stopped for the wrapped event.
     *
     * @return bool whether the wrapped event stops propagation
     */
    public function isPropagationStopped(): bool
    {
        return $this->event instanceof StoppableEventInterface
            && $this->event->isPropagationStopped();
    }
}
