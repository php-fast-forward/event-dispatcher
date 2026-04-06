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

use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use stdClass;
use FastForward\EventDispatcher\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuntimeException::class)]
final class RuntimeExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testForUnsupportedTypeWillReturnProperExceptionInstance(): void
    {
        $listener  = new stdClass();
        $exception = RuntimeException::forUnsupportedType($listener);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            \sprintf('Unsupported listener type: "%s".', get_debug_type($listener)),
            $exception->getMessage()
        );
    }

    /**
     * @return void
     */
    public function testForUnsupportedTypeWithScalar(): void
    {
        $listener  = 42;
        $exception = RuntimeException::forUnsupportedType($listener);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Unsupported listener type: "int".', $exception->getMessage());
    }

    /**
     * @return void
     */
    public function testForUnsupportedTypeWithArray(): void
    {
        $listener  = ['not', 'a', 'callable'];
        $exception = RuntimeException::forUnsupportedType($listener);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Unsupported listener type: "array".', $exception->getMessage());
    }

    /**
     * @return void
     */
    public function testForMethodWithoutParameters(): void
    {
        $exception = RuntimeException::forMethodWithoutParameters();

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Method has no parameters', $exception->getMessage());
    }

    /**
     * @return void
     */
    public function testForMethodParameterWithoutType(): void
    {
        $exception = RuntimeException::forMethodParameterWithoutType();

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Parameter has no type', $exception->getMessage());
    }

    /**
     * @return void
     */
    public function testForListenerWithoutParameters(): void
    {
        $exception = RuntimeException::forListenerWithoutParameters();

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Listener has no parameters', $exception->getMessage());
    }

    /**
     * @return void
     */
    public function testForListenerParameterWithoutType(): void
    {
        $exception = RuntimeException::forListenerParameterWithoutType();

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Listener parameter has no type', $exception->getMessage());
    }

    /**
     * @return void
     */
    #[Test]
    public function testForCacheInvalidationFailure(): void
    {
        $exception = RuntimeException::forCacheInvalidationFailure(['users:1', 'users:list']);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Failed to invalidate cache keys: "users:1", "users:list".', $exception->getMessage());
    }

    /**
     * @return void
     */
    #[Test]
    public function testForWebhookRequestFailure(): void
    {
        $exception = RuntimeException::forWebhookRequestFailure('https://example.test/hooks', 500);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Webhook request to "https://example.test/hooks" failed with status code 500.',
            $exception->getMessage(),
        );
    }

    /**
     * @return void
     */
    public function testConstructorIsPrivate(): void
    {
        $constructor = new ReflectionMethod(RuntimeException::class, '__construct');

        self::assertTrue($constructor->isPrivate());
    }
}
