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

use FastForward\EventDispatcher\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends TestCase
{
    public function testForExpectedArrayListWillReturnProperExceptionInstance(): void
    {
        $array     = ['key' => 'value'];
        $exception = InvalidArgumentException::forExpectedArrayList($array);

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame(
            'Array must be a list, {"key":"value"} given',
            $exception->getMessage()
        );
    }
}
