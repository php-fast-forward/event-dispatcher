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

namespace FastForward\EventDispatcher\Tests\Exception;

use FastForward\EventDispatcher\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuntimeException::class)]
final class RuntimeExceptionTest extends TestCase
{
    public function testForUnsupportedTypeWillReturnProperExceptionInstance(): void
    {
        $listener  = new \stdClass();
        $exception = RuntimeException::forUnsupportedType($listener);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            \sprintf('Unsupported listener type: "%s".', get_debug_type($listener)),
            $exception->getMessage()
        );
    }

    public function testForUnsupportedTypeWithScalar(): void
    {
        $listener  = 42;
        $exception = RuntimeException::forUnsupportedType($listener);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Unsupported listener type: "int".',
            $exception->getMessage()
        );
    }

    public function testForUnsupportedTypeWithArray(): void
    {
        $listener  = ['not', 'a', 'callable'];
        $exception = RuntimeException::forUnsupportedType($listener);

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame(
            'Unsupported listener type: "array".',
            $exception->getMessage()
        );
    }
}
