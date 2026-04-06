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

namespace FastForward\EventDispatcher\Tests\ServiceProvider\Configuration;

use stdClass;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredPrioritizedListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfiguredPrioritizedListener::class)]
final class ConfiguredPrioritizedListenerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillStoreTheProvidedValues(): void
    {
        $listener   = new stdClass();
        $configured = new ConfiguredPrioritizedListener($listener, 'event.type', 'handle', 25);

        self::assertSame($listener, $configured->listener);
        self::assertSame('event.type', $configured->eventType);
        self::assertSame('handle', $configured->method);
        self::assertSame(25, $configured->priority);
    }
}
