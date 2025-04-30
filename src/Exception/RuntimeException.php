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
 * Class RuntimeException.
 *
 * Exception type specific to event dispatcher runtime errors.
 * This exception MUST be used when encountering illegal states or
 * unsupported input during runtime execution within the event dispatcher logic.
 */
final class RuntimeException extends \RuntimeException
{
    /**
     * Generates an exception indicating an unsupported listener type.
     *
     * This method SHALL be used when a listener is provided in a format
     * that is not callable or otherwise not supported by the dispatcher.
     *
     * @param mixed $listener The listener instance that caused the error.
     *                        This MAY be of any type, and will be introspected.
     *
     * @return self an instance of RuntimeException with a descriptive message
     */
    public static function forUnsupportedType(mixed $listener): self
    {
        return new self(\sprintf(
            'Unsupported listener type: "%s".',
            get_debug_type($listener)
        ));
    }
}
