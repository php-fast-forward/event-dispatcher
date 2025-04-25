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

namespace FastForward\EventDispatcher\Exception;

use Psr\Container\ContainerExceptionInterface;

final class ContainerException extends \Exception implements ContainerExceptionInterface
{
    public static function forInvalidService(string $id, \Throwable $previous): self
    {
        return new self(\sprintf('Invalid service "%s".', $id), 0, $previous);
    }
}
