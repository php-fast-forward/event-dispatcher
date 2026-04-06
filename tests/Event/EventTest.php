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

use FastForward\EventDispatcher\Event\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

#[CoversClass(Event::class)]
final class EventTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testConcreteEventProvidesSymfonyCompatibleStoppableBehavior(): void
    {
        $event = new Event();

        self::assertInstanceOf(StoppableEventInterface::class, $event);
        self::assertInstanceOf(SymfonyEvent::class, $event);
        self::assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        self::assertTrue($event->isPropagationStopped());
    }
}
