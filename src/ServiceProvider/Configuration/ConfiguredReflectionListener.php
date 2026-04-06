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
 * Store one reflection-based listener definition loaded from configuration.
 *
 * @internal
 */
final readonly class ConfiguredReflectionListener
{
    /**
     * Create a configured reflection listener entry.
     *
     * @param mixed $listener listener value to register
     * @param string $eventType event type resolved from the listener signature
     */
    public function __construct(
        public mixed $listener,
        public string $eventType,
    ) {}
}
