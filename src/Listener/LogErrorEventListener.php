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

namespace FastForward\EventDispatcher\Listener;

use FastForward\EventDispatcher\Event\ErrorEvent;
use Psr\Log\LoggerInterface;

/**
 * Log dispatcher error events through a PSR-3 logger.
 *
 * The logged context includes both the throwable and the emitted {@see ErrorEvent}.
 */
final readonly class LogErrorEventListener
{
    /**
     * Create a listener that records dispatcher failures.
     *
     * @param LoggerInterface $logger logger used to record error events
     */
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Log the throwable carried by the provided error event.
     *
     * @param ErrorEvent $event error event emitted by the dispatcher
     */
    public function __invoke(ErrorEvent $event): void
    {
        $throwable = $event->getThrowable();

        $this->logger->error('An error occurred during event dispatching', [
            'exception' => $throwable,
            'event' => $event,
        ]);
    }
}
