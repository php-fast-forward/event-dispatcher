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
use ArrayObject;
use FastForward\EventDispatcher\Event\NamedEvent;
use FastForward\EventDispatcher\Exception\InvalidArgumentException;
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
#[UsesClass(InvalidArgumentException::class)]
final class EventSubscriberListenerProviderTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetListenersForEventWithoutSubscribersWillReturnEmptyIterator(): void
    {
        $event    = new stdClass();
        $provider = new EventSubscriberListenerProvider();

        self::assertCount(0, iterator_to_array($provider->getListenersForEvent($event)));
    }

    /**
     * @return void
     */
    public function testGetListenersForEventWillYieldListenersInPriorityOrder(): void
    {
        $event = new stdClass();

        $subscriber = new class implements EventSubscriberInterface {
            public static array $called = [];

            /**
             * @return array
             */
            public static function getSubscribedEvents(): array
            {
                return [
                    stdClass::class => [['onEventA', 10], ['onEventB', 20], ['onEventC', 5]],
                ];
            }

            /**
             * @param mixed $e
             *
             * @return void
             */
            public function onEventA($e): void
            {
                self::$called[] = 'A';
            }

            /**
             * @param mixed $e
             *
             * @return void
             */
            public function onEventB($e): void
            {
                self::$called[] = 'B';
            }

            /**
             * @param mixed $e
             *
             * @return void
             */
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

    /**
     * @return void
     */
    public function testGetListenersForEventWillYieldListenersInSubscribersPriorityOrder(): void
    {
        $event = new stdClass();
        $calls = new ArrayObject();

        $subscriber1 = new readonly class ($calls) implements EventSubscriberInterface {
            /**
             * @param ArrayObject $calls
             */
            public function __construct(
                private ArrayObject $calls
            ) {}

            /**
             * @return array
             */
            public static function getSubscribedEvents(): array
            {
                return [
                    stdClass::class => ['onEventA', 10],
                ];
            }

            /**
             * @param mixed $e
             *
             * @return void
             */
            public function onEventA($e): void
            {
                $this->calls->append('A');
            }
        };

        $subscriber2 = new readonly class ($calls) implements EventSubscriberInterface {
            /**
             * @param ArrayObject $calls
             */
            public function __construct(
                private ArrayObject $calls
            ) {}

            /**
             * @return array
             */
            public static function getSubscribedEvents(): array
            {
                return [
                    stdClass::class => ['onEventB', 20],
                ];
            }

            /**
             * @param mixed $e
             *
             * @return void
             */
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

    /**
     * @return void
     */
    public function testGetListenersForNamedEventResolvesByName(): void
    {
        $event = new stdClass();

        $subscriber = new class implements EventSubscriberInterface {
            public static bool $called = false;

            /**
             * @return array
             */
            public static function getSubscribedEvents(): array
            {
                return [
                    'custom.event' => 'handle',
                ];
            }

            /**
             * @param mixed $e
             *
             * @return void
             */
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

    /**
     * @return void
     */
    public function testSubscribeThrowsIfStringClassIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Event subscriber "stdClass" must implement "Symfony\Component\EventDispatcher\EventSubscriberInterface"'
        );

        new EventSubscriberListenerProvider('stdClass');
    }

    /**
     * @return void
     */
    public function testSubscriberCanUseSingleMethodStringSyntax(): void
    {
        $event = new stdClass();

        $subscriber = new class implements EventSubscriberInterface {
            public static bool $called = false;

            /**
             * @return array
             */
            public static function getSubscribedEvents(): array
            {
                return [
                    stdClass::class => 'onEvent',
                ];
            }

            /**
             * @param mixed $e
             *
             * @return void
             */
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
