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

use FastForward\EventDispatcher\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ContainerException::class)]
final class ContainerExceptionTest extends TestCase
{
    public function testForInvalidServiceWillReturnProperException(): void
    {
        $id       = uniqid('service.id');
        $previous = new \RuntimeException('Underlying issue', 500);

        $exception = ContainerException::forInvalidService($id, $previous);

        self::assertInstanceOf(ContainerException::class, $exception);
        self::assertInstanceOf(ContainerExceptionInterface::class, $exception);
        self::assertSame(\sprintf('Invalid service "%s".', $id), $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(500, $exception->getCode());
    }
}
