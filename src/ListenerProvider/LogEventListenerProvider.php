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

use FastForward\EventDispatcher\Event\ErrorEvent;
use FastForward\EventDispatcher\Event\NamedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Log every dispatched object through a PSR-3 logger.
 */
final class LogEventListenerProvider extends WildcardListenerProvider
{
    /**
     * Create a listener that records dispatched events.
     *
     * @param LoggerInterface $logger logger used to record the event
     * @param string $level PSR-3 log level
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $level = LogLevel::INFO,
    ) {}

    /**
     * Log the provided event.
     *
     * @param object $event event emitted by the dispatcher
     */
    public function __invoke(object $event): void
    {
        $this->logger->log($this->level, 'Event dispatched', $this->createContext($event));
    }

    /**
     * Create the log context for the event.
     *
     * @param object $event event being logged
     *
     * @return array<string, mixed> log context
     */
    private function createContext(object $event): array
    {
        $context = [
            'event' => $event,
            'event_class' => $event::class,
        ];

        if ($event instanceof NamedEvent) {
            $context['event_name'] = $event->getName();
            $context['wrapped_event'] = $event->getEvent();
            $context['wrapped_event_class'] = $event->getEvent()::class;
        }

        if ($event instanceof ErrorEvent) {
            $context['exception'] = $event->getThrowable();
            $context['original_event'] = $event->getEvent();
            $context['original_event_class'] = $event->getEvent()::class;
        }

        return $context;
    }
}
