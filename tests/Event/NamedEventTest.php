<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/event-dispatcher.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @link      https://github.com/php-fast-forward/event-dispatcher
 * @copyright Copyright (c) 2025 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\EventDispatcher\Tests\Event;

use FastForward\EventDispatcher\Event\NamedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[CoversClass(NamedEvent::class)]
#[UsesClass(Event::class)]
final class NamedEventTest extends TestCase
{
    public function testConstructorStoresNameAndEvent(): void
    {
        $originalEvent = new \stdClass();
        $name          = uniqid('event.name');

        $namedEvent = new NamedEvent($originalEvent, $name);

        self::assertSame($name, $namedEvent->getName());
        self::assertSame($originalEvent, $namedEvent->getEvent());
    }

    public function testConstructorWithoutNameWillUseClassName(): void
    {
        $originalEvent = new \stdClass();
        $name          = \get_class($originalEvent);

        $namedEvent = new NamedEvent($originalEvent);

        self::assertSame($name, $namedEvent->getName());
        self::assertSame($originalEvent, $namedEvent->getEvent());
    }
}
