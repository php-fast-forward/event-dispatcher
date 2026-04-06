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

namespace FastForward\EventDispatcher\ServiceProvider\Configuration;

/**
 * Store one prioritized listener definition loaded from configuration.
 *
 * @internal
 */
final readonly class ConfiguredPrioritizedListener
{
    /**
     * Create a configured prioritized listener entry.
     *
     * @param object|string $listener listener instance or listener class name
     * @param string $eventType event type handled by the listener
     * @param string $method listener method to invoke
     * @param int $priority Listener priority. Higher values run first.
     */
    public function __construct(
        public object|string $listener,
        public string $eventType,
        public string $method,
        public int $priority = 0,
    ) {}
}
