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

namespace FastForward\EventDispatcher\Tests\Event;

use RuntimeException;
use stdClass;
use FastForward\EventDispatcher\Event\ErrorEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorEvent::class)]
final class ErrorEventTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillExposeOriginalContext(): void
    {
        $event     = new stdClass();
        $listener  = static fn(object $dispatchedEvent): null => null;
        $throwable = new RuntimeException('Listener failed', 123);
        $errorEvent = new ErrorEvent($event, $listener, $throwable);

        self::assertSame('Listener failed', $errorEvent->getMessage());
        self::assertSame(123, $errorEvent->getCode());
        self::assertSame($throwable, $errorEvent->getPrevious());
        self::assertSame($event, $errorEvent->getEvent());
        self::assertSame($listener, $errorEvent->getListener());
        self::assertSame($throwable, $errorEvent->getThrowable());
        self::assertFalse($errorEvent->isPropagationStopped());
    }

    /**
     * @return void
     */
    #[Test]
    public function testPropagationCanStartStoppedOrBeStoppedLater(): void
    {
        $initiallyStopped = new ErrorEvent(
            new stdClass(),
            static fn(object $dispatchedEvent): null => null,
            new RuntimeException('Initial'),
            true,
        );

        self::assertTrue($initiallyStopped->isPropagationStopped());

        $errorEvent = new ErrorEvent(
            new stdClass(),
            static fn(object $dispatchedEvent): null => null,
            new RuntimeException('Later'),
        );

        $errorEvent->stopPropagation();

        self::assertTrue($errorEvent->isPropagationStopped());
    }
}
