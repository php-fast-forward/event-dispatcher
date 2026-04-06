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

namespace FastForward\EventDispatcher\Tests\Listener;

use RuntimeException;
use stdClass;
use FastForward\EventDispatcher\Event\ErrorEvent;
use FastForward\EventDispatcher\Listener\LogErrorEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

#[CoversClass(LogErrorEventListener::class)]
#[UsesClass(ErrorEvent::class)]
final class LogErrorEventListenerTest extends TestCase
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
    public function testInvokeWillLogTheThrowableAndEventContext(): void
    {
        $throwable = new RuntimeException('Listener failed');
        $event     = new ErrorEvent(
            new stdClass(),
            static fn(object $dispatchedEvent): null => null,
            $throwable,
        );

        $this->logger
            ->error(
                'An error occurred during event dispatching',
                Argument::that(static fn(array $context): bool => $context['exception'] === $throwable
                    && $context['event'] === $event),
            )
            ->shouldBeCalled();

        $listener = new LogErrorEventListener($this->logger->reveal());

        $listener($event);
    }
}
