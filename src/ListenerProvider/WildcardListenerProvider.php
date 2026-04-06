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

namespace FastForward\EventDispatcher\ListenerProvider;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Expose an invokable wildcard listener as a PSR-14 listener provider.
 */
abstract class WildcardListenerProvider implements ListenerProviderInterface
{
    /**
     * Handle the provided event.
     *
     * @param object $event event emitted by the dispatcher
     */
    abstract public function __invoke(object $event): void;

    /**
     * Yield the current listener for any dispatched object.
     *
     * @param object $event event being dispatched
     *
     * @return iterable<callable(object): void> matching listeners
     */
    final public function getListenersForEvent(object $event): iterable
    {
        yield $this;
    }
}
