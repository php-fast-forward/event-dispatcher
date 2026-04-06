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

use RuntimeException;
use stdClass;
use FastForward\EventDispatcher\Event\ErrorEvent;
use FastForward\EventDispatcher\Event\NamedEvent;
use FastForward\EventDispatcher\ListenerProvider\LogEventListenerProvider;
use FastForward\EventDispatcher\ListenerProvider\WildcardListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

#[CoversClass(LogEventListenerProvider::class)]
#[UsesClass(WildcardListenerProvider::class)]
#[UsesClass(NamedEvent::class)]
#[UsesClass(ErrorEvent::class)]
final class LogEventListenerProviderTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $logger;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillLogGenericEventContextWithDefaultLevel(): void
    {
        $event = new stdClass();

        $this->logger
            ->log(
                LogLevel::INFO,
                'Event dispatched',
                Argument::that(static fn(array $context): bool => $context['event'] === $event
                    && $context['event_class'] === $event::class
                    && ! isset($context['event_name'])
                    && ! isset($context['exception'])),
            )
            ->shouldBeCalled();

        $provider = new LogEventListenerProvider($this->logger->reveal());

        $provider($event);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillLogNamedEventContext(): void
    {
        $event = new stdClass();
        $name  = uniqid('named.', true);

        $namedEvent = new NamedEvent($event, $name);

        $this->logger
            ->log(
                LogLevel::NOTICE,
                'Event dispatched',
                Argument::that(static fn(array $context): bool => $context['event'] === $namedEvent
                    && NamedEvent::class === $context['event_class']
                    && $context['event_name'] === $name
                    && $context['wrapped_event'] === $event
                    && $context['wrapped_event_class'] === $event::class),
            )
            ->shouldBeCalled();

        $provider = new LogEventListenerProvider($this->logger->reveal(), LogLevel::NOTICE);

        $provider($namedEvent);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillLogErrorEventContext(): void
    {
        $event     = new stdClass();
        $throwable = new RuntimeException('Listener failed');
        $errorEvent = new ErrorEvent(
            $event,
            static fn(object $dispatchedEvent): null => null,
            $throwable,
        );

        $this->logger
            ->log(
                LogLevel::WARNING,
                'Event dispatched',
                Argument::that(static fn(array $context): bool => $context['event'] === $errorEvent
                    && ErrorEvent::class === $context['event_class']
                    && $context['exception'] === $throwable
                    && $context['original_event'] === $event
                    && $context['original_event_class'] === $event::class),
            )
            ->shouldBeCalled();

        $provider = new LogEventListenerProvider($this->logger->reveal(), LogLevel::WARNING);

        $provider($errorEvent);
    }
}
