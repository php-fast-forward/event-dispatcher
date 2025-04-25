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

final class InvalidArgumentException extends \InvalidArgumentException
{
    public static function forExpectedArrayList(array $array): self
    {
        return new self(\sprintf('Array must be a list, %s given', json_encode($array)));
    }
}
