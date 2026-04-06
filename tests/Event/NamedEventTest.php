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
use FastForward\EventDispatcher\Event\StoppableEventTrait;
use stdClass;
use FastForward\EventDispatcher\Event\NamedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamedEvent::class)]
#[UsesClass(Event::class)]
#[UsesTrait(StoppableEventTrait::class)]
final class NamedEventTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testConstructorStoresNameAndEvent(): void
    {
        $originalEvent = new stdClass();
        $name          = uniqid('event.name');

        $namedEvent = new NamedEvent($originalEvent, $name);

        self::assertSame($name, $namedEvent->getName());
        self::assertSame($originalEvent, $namedEvent->getEvent());
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWithoutNameWillUseClassName(): void
    {
        $originalEvent = new stdClass();
        $name          = $originalEvent::class;

        $namedEvent = new NamedEvent($originalEvent);

        self::assertSame($name, $namedEvent->getName());
        self::assertSame($originalEvent, $namedEvent->getEvent());
    }

    /**
     * @return void
     */
    #[Test]
    public function testIsPropagationStoppedWillReturnFalseForNonStoppableWrappedEvent(): void
    {
        self::assertFalse(new NamedEvent(new stdClass())->isPropagationStopped());
    }

    /**
     * @return void
     */
    #[Test]
    public function testIsPropagationStoppedWillMirrorWrappedEventBase(): void
    {
        $event = new Event();

        $namedEvent = new NamedEvent($event);

        self::assertFalse($namedEvent->isPropagationStopped());

        $event->stopPropagation();

        self::assertTrue($namedEvent->isPropagationStopped());
    }
}
