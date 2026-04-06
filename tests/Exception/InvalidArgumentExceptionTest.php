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

namespace FastForward\EventDispatcher\Tests\Exception;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ReflectionMethod;
use FastForward\EventDispatcher\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testForExpectedArrayListWillReturnProperExceptionInstance(): void
    {
        $array     = [
            'key' => 'value',
        ];
        $exception = InvalidArgumentException::forExpectedArrayList($array);

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame('Array must be a list, {"key":"value"} given', $exception->getMessage());
    }

    /**
     * @return void
     */
    public function testForInvalidEventSubscriberWillReturnProperExceptionInstance(): void
    {
        $exception = InvalidArgumentException::forInvalidEventSubscriber(
            'stdClass',
            EventSubscriberInterface::class,
        );

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame(
            'Event subscriber "stdClass" must implement "Symfony\Component\EventDispatcher\EventSubscriberInterface"',
            $exception->getMessage(),
        );
    }

    /**
     * @return void
     */
    public function testConstructorIsPrivate(): void
    {
        $constructor = new ReflectionMethod(InvalidArgumentException::class, '__construct');

        self::assertTrue($constructor->isPrivate());
    }
}
