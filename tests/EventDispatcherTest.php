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

namespace FastForward\EventDispatcher\Tests;

use FastForward\EventDispatcher\Event\NamedEvent;
use FastForward\EventDispatcher\EventDispatcher;
use FastForward\Iterator\ChainIterableIterator;
use FastForward\Iterator\UniqueIteratorIterator;
use Phly\EventDispatcher\ErrorEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * @internal
 */
#[CoversClass(EventDispatcher::class)]
#[UsesClass(UniqueIteratorIterator::class)]
#[UsesClass(ChainIterableIterator::class)]
#[UsesClass(NamedEvent::class)]
#[UsesClass(ErrorEvent::class)]
final class EventDispatcherTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $listenerProvider;

    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->listenerProvider = $this->prophesize(ListenerProviderInterface::class);

        $this->listenerProvider->getListenersForEvent(Argument::type('object'))->willReturn([]);

        $this->dispatcher = new EventDispatcher($this->listenerProvider->reveal());
    }

    public function testDispatchWillInvokeAllListeners(): void
    {
        $event = new \stdClass();

        $listener1 = static fn (object $event) => $event->invoked1 = true;
        $listener2 = static fn (object $event) => $event->invoked2 = true;

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
        self::assertTrue($event->invoked1);
        self::assertTrue($event->invoked2);
    }

    public function testDispatchWillNotHandleDuplicatedListeners(): void
    {
        $event           = new \stdClass();
        $event->invoked1 = 0;
        $event->invoked2 = 0;

        $eventName = uniqid();

        $listener1 = static fn (object $event) => ++$event->invoked1;
        $listener2 = static fn (object $event) => ++$event->invoked2;

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
        self::assertSame(1, $event->invoked1);
        self::assertSame(1, $event->invoked2);
    }

    public function testDispatchWillReturnImmediatelyIfPropagationIsStopped(): void
    {
        $event = $this->prophesize(StoppableEventInterface::class);
        $event->isPropagationStopped()->willReturn(true);

        self::assertSame(
            $event->reveal(),
            $this->dispatcher->dispatch($event->reveal())
        );
    }

    public function testDispatchWillStopWhenEventPropagationIsStopped(): void
    {
        $event = $this->prophesize(StoppableEventInterface::class);
        $event->isPropagationStopped()->willReturn(false, true);

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

    public function testDispatchWillDispatchErrorEventOnThrowable(): void
    {
        $event     = new \stdClass();
        $exception = new \RuntimeException('Listener error');
        $listener  = static function () use ($exception): void {
            throw $exception;
        };

        $this->listenerProvider
            ->getListenersForEvent($event)
            ->willReturn([$listener])
            ->shouldBeCalled()
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Listener error');

        $this->dispatcher->dispatch($event);
    }

    public function testDispatchWithErrorEventWillRethrowOriginalThrowable(): void
    {
        $original   = new \RuntimeException('Original');
        $errorEvent = new ErrorEvent(new \stdClass(), static fn () => null, $original);

        $listener = static function () use ($errorEvent): void {
            throw $errorEvent;
        };

        $this->listenerProvider
            ->getListenersForEvent($errorEvent)
            ->willReturn([$listener])
            ->shouldBeCalled()
        ;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Original');

        $this->dispatcher->dispatch($errorEvent);
    }
}
