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

/**
 * Class InvalidArgumentException.
 *
 * Specialized exception for invalid arguments within the event dispatcher context.
 * This exception type MUST be thrown when the method receives an argument
 * that violates its expected input structure or type constraints.
 */
final class InvalidArgumentException extends \InvalidArgumentException
{
    /**
     * Generates an exception indicating that an array was expected to be a list.
     *
     * This method SHALL be used when an array argument is required to be a list
     * (i.e., sequential integers starting from zero), but a different structure is detected (e.g., associative array).
     *
     * @param array $array the array that failed the list expectation
     *
     * @return self an instance of InvalidArgumentException with a descriptive message
     */
    public static function forExpectedArrayList(array $array): self
    {
        return new self(\sprintf('Array must be a list, %s given', json_encode($array)));
    }
}
