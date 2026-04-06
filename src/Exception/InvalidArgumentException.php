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

namespace FastForward\EventDispatcher\Exception;

use Throwable;

/**
 * Represent invalid arguments detected by the event dispatcher package.
 */
final class InvalidArgumentException extends \InvalidArgumentException
{
    /**
     * Prevent direct instantiation outside the named factories.
     *
     * @param string $message exception message
     * @param int $code exception code
     * @param Throwable|null $previous previous exception
     */
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception for an array that was expected to be a list.
     *
     * @param array<array-key, mixed> $array array that failed the list assertion
     *
     * @return self exception describing the invalid array shape
     */
    public static function forExpectedArrayList(array $array): self
    {
        return new self(\sprintf('Array must be a list, %s given', json_encode($array)));
    }

    /**
     * Create an exception for a subscriber class that does not implement the required contract.
     *
     * @param string $eventSubscriber subscriber class name
     * @param string $expectedInterface required subscriber interface
     *
     * @return self exception describing the invalid subscriber class
     */
    public static function forInvalidEventSubscriber(string $eventSubscriber, string $expectedInterface): self
    {
        return new self(\sprintf(
            'Event subscriber "%s" must implement "%s"',
            $eventSubscriber,
            $expectedInterface,
        ));
    }
}
