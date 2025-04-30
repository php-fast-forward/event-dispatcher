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

namespace FastForward\EventDispatcher\Tests\ListenerProvider;

use FastForward\EventDispatcher\Event\NamedEvent;
use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[CoversClass(EventSubscriberListenerProvider::class)]
#[UsesClass(NamedEvent::class)]
final class EventSubscriberListenerProviderTest extends TestCase
{
    public function testGetListenersForEventWithoutSubscribersWillReturnEmptyIterator(): void
    {
        $event    = new \stdClass();
        $provider = new EventSubscriberListenerProvider();

        self::assertCount(0, iterator_to_array($provider->getListenersForEvent($event)));
    }

    public function testGetListenersForEventWillYieldListenersInPriorityOrder(): void
    {
        $event = new \stdClass();

        $subscriber = new class implements EventSubscriberInterface {
            public static array $called = [];

            public static function getSubscribedEvents(): array
            {
                return [
                    \stdClass::class => [
                        ['onEventA', 10],
                        ['onEventB', 20],
                        ['onEventC', 5],
                    ],
                ];
            }

            public function onEventA($e): void
            {
                self::$called[] = 'A';
            }

            public function onEventB($e): void
            {
                self::$called[] = 'B';
            }

            public function onEventC($e): void
            {
                self::$called[] = 'C';
            }
        };

        $provider = new EventSubscriberListenerProvider($subscriber);

        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $subscriber::$called = [];
        foreach ($listeners as $listener) {
            $listener($event);
        }

        self::assertSame(['B', 'A', 'C'], $subscriber::$called);
    }

    public function testGetListenersForEventWillYieldListenersInSubscribersPriorityOrder(): void
    {
        $event = new \stdClass();
        $calls = new \ArrayObject();

        $subscriber1 = new class($calls) implements EventSubscriberInterface {
            public function __construct(private \ArrayObject $calls) {}

            public static function getSubscribedEvents(): array
            {
                return [
                    \stdClass::class => ['onEventA', 10],
                ];
            }

            public function onEventA($e): void
            {
                $this->calls->append('A');
            }
        };

        $subscriber2 = new class($calls) implements EventSubscriberInterface {
            public function __construct(private \ArrayObject $calls) {}

            public static function getSubscribedEvents(): array
            {
                return [
                    \stdClass::class => ['onEventB', 20],
                ];
            }

            public function onEventB($e): void
            {
                $this->calls->append('B');
            }
        };

        $provider  = new EventSubscriberListenerProvider($subscriber1, $subscriber2);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        foreach ($listeners as $listener) {
            $listener($event);
        }

        self::assertSame(['B', 'A'], $calls->getArrayCopy());
    }

    public function testGetListenersForNamedEventResolvesByName(): void
    {
        $event = new \stdClass();

        $subscriber = new class implements EventSubscriberInterface {
            public static bool $called = false;

            public static function getSubscribedEvents(): array
            {
                return ['custom.event' => 'handle'];
            }

            public function handle($e): void
            {
                self::$called = true;
            }
        };

        $provider   = new EventSubscriberListenerProvider($subscriber);
        $namedEvent = new NamedEvent($event, 'custom.event');

        $listeners = iterator_to_array($provider->getListenersForEvent($namedEvent));

        $subscriber::$called = false;
        foreach ($listeners as $listener) {
            $listener($namedEvent);
        }

        self::assertTrue($subscriber::$called);
    }

    public function testSubscribeThrowsIfStringClassIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event subscriber "stdClass" must implement "Symfony\Component\EventDispatcher\EventSubscriberInterface"');

        new EventSubscriberListenerProvider('stdClass');
    }

    public function testSubscriberCanUseSingleMethodStringSyntax(): void
    {
        $event = new \stdClass();

        $subscriber = new class implements EventSubscriberInterface {
            public static bool $called = false;

            public static function getSubscribedEvents(): array
            {
                return [\stdClass::class => 'onEvent'];
            }

            public function onEvent($e): void
            {
                self::$called = true;
            }
        };

        $provider = new EventSubscriberListenerProvider($subscriber);

        $subscriber::$called = false;
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertTrue($subscriber::$called);
    }
}
