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

namespace FastForward\EventDispatcher\Tests\ListenerProvider;

use stdClass;
use FastForward\EventDispatcher\ListenerProvider\WildcardListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WildcardListenerProvider::class)]
final class WildcardListenerProviderTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testGetListenersForEventWillYieldTheProviderInstance(): void
    {
        $event = new stdClass();

        $provider = new class extends WildcardListenerProvider {
            /**
             * @var list<object>
             */
            public array $received = [];

            /**
             * @param object $event
             *
             * @return void
             */
            public function __invoke(object $event): void
            {
                $this->received[] = $event;
            }
        };

        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        self::assertCount(1, $listeners);
        self::assertSame($provider, $listeners[0]);

        $listeners[0]($event);

        self::assertSame([$event], $provider->received);
    }
}
