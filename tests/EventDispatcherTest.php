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

namespace FastForward\EventDispatcher\Tests;

use stdClass;
use RuntimeException;
use FastForward\EventDispatcher\Event\NamedEvent;
use FastForward\EventDispatcher\EventDispatcher;
use FastForward\EventDispatcher\Event\ErrorEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

#[CoversClass(EventDispatcher::class)]
#[UsesClass(NamedEvent::class)]
#[UsesClass(ErrorEvent::class)]
final class EventDispatcherTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $listenerProvider;

    private EventDispatcher $dispatcher;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->listenerProvider = $this->prophesize(ListenerProviderInterface::class);

        $this->listenerProvider->getListenersForEvent(Argument::type('object'))->willReturn([]);

        $this->dispatcher = new EventDispatcher($this->listenerProvider->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchWillInvokeAllListeners(): void
    {
        $event = new class {
            public array $calls = [];
        };
        $namedEvent = null;

        $listener1 = static function (object $dispatchedEvent) use ($event): void {
            $event->calls[] = $dispatchedEvent::class;
        };

        $listener2 = static function (NamedEvent $dispatchedEvent) use (&$namedEvent): void {
            $namedEvent = $dispatchedEvent;
            $dispatchedEvent->getEvent()
                ->calls[] = 'named';
        };

        $this->listenerProvider
            ->getListenersForEvent($event)
            ->willReturn([$listener1])
            ->shouldBeCalled()
        ;

        $this->listenerProvider
            ->getListenersForEvent(new NamedEvent($event))
            ->willReturn([$listener2])
            ->shouldBeCalled()
        ;

        $result = $this->dispatcher->dispatch($event);

        self::assertSame($event, $result);
        self::assertSame([$event::class, 'named'], $event->calls);
        self::assertInstanceOf(NamedEvent::class, $namedEvent);
        self::assertSame($event, $namedEvent->getEvent());
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchWillInvokeTheSameListenerForOriginalAndNamedDispatches(): void
    {
        $event = new class {
            public array $calls = [];
        };

        $eventName = uniqid();

        $listener1 = static function (object $dispatchedEvent) use ($event): void {
            $event->calls[] = $dispatchedEvent::class;
        };

        $listener2 = static function (object $dispatchedEvent) use ($event): void {
            $event->calls[] = 'secondary:' . $dispatchedEvent::class;
        };

        $this->listenerProvider
            ->getListenersForEvent($event)
            ->willReturn([$listener1, $listener2])
            ->shouldBeCalled()
        ;

        $this->listenerProvider
            ->getListenersForEvent(new NamedEvent($event, $eventName))
            ->willReturn([$listener1])
            ->shouldBeCalled()
        ;

        $result = $this->dispatcher->dispatch($event, $eventName);

        self::assertSame($event, $result);
        self::assertSame([$event::class, 'secondary:' . $event::class, NamedEvent::class], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchWillReturnImmediatelyIfPropagationIsStopped(): void
    {
        $event = $this->prophesize(StoppableEventInterface::class);
        $event->isPropagationStopped()
            ->willReturn(true);

        self::assertSame($event->reveal(), $this->dispatcher->dispatch($event->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchWillStopWhenEventPropagationIsStopped(): void
    {
        $event = $this->prophesize(StoppableEventInterface::class);
        $event->isPropagationStopped()
            ->willReturn(false, true);

        $called   = false;
        $listener = static function ($e) use (&$called): void {
            $called = true;
        };

        $this->listenerProvider
            ->getListenersForEvent($event->reveal())
            ->willReturn([$listener])
            ->shouldBeCalled()
        ;

        $this->dispatcher->dispatch($event->reveal());

        self::assertTrue($called);
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchWillBreakBeforeInvokingRemainingListenersWhenPropagationStopsMidIteration(): void
    {
        $event = $this->prophesize(StoppableEventInterface::class);
        $event->isPropagationStopped()
            ->willReturn(false, true, true);

        $calls = [];

        $listener1 = static function () use (&$calls): void {
            $calls[] = 'first';
        };
        $listener2 = static function () use (&$calls): void {
            $calls[] = 'second';
        };

        $this->listenerProvider
            ->getListenersForEvent($event->reveal())
            ->willReturn([$listener1, $listener2])
            ->shouldBeCalled()
        ;

        $this->dispatcher->dispatch($event->reveal());

        self::assertSame(['first'], $calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchWillDispatchErrorEventOnThrowable(): void
    {
        $event     = new stdClass();
        $exception = new RuntimeException('Listener error');
        $listener  = static function () use ($exception): never {
            throw $exception;
        };

        $this->listenerProvider
            ->getListenersForEvent($event)
            ->willReturn([$listener])
            ->shouldBeCalled()
        ;

        $this->listenerProvider
            ->getListenersForEvent(Argument::type(ErrorEvent::class))
            ->willReturn([])
            ->shouldBeCalled()
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Listener error');

        $this->dispatcher->dispatch($event);
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchWithErrorEventWillRethrowOriginalThrowable(): void
    {
        $original   = new RuntimeException('Original');
        $errorEvent = new ErrorEvent(new stdClass(), static fn(): null => null, $original);

        $listener = static function () use ($errorEvent): never {
            throw $errorEvent;
        };

        $this->listenerProvider
            ->getListenersForEvent($errorEvent)
            ->willReturn([$listener])
            ->shouldBeCalled()
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Original');

        $this->dispatcher->dispatch($errorEvent);
    }

    /**
     * @return void
     */
    #[Test]
    public function testDispatchOfNamedEventWillReturnTheWrappedEvent(): void
    {
        $event      = new stdClass();
        $namedEvent = new NamedEvent($event, 'custom.event');

        $this->listenerProvider
            ->getListenersForEvent($namedEvent)
            ->willReturn([])
            ->shouldBeCalled()
        ;

        self::assertSame($event, $this->dispatcher->dispatch($namedEvent));
    }
}
