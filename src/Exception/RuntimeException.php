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
 * Represent runtime failures raised by the event dispatcher package.
 */
final class RuntimeException extends \RuntimeException
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
     * Create an exception for an unsupported listener value.
     *
     * @param mixed $listener listener value that could not be resolved
     *
     * @return self exception describing the unsupported listener type
     */
    public static function forUnsupportedType(mixed $listener): self
    {
        return new self(\sprintf('Unsupported listener type: "%s".', get_debug_type($listener)));
    }

    /**
     * Create an exception for a reflected method without parameters.
     *
     * @return self exception describing the invalid reflected method
     */
    public static function forMethodWithoutParameters(): self
    {
        return new self('Method has no parameters');
    }

    /**
     * Create an exception for a reflected method whose first parameter has no type.
     *
     * @return self exception describing the missing method parameter type
     */
    public static function forMethodParameterWithoutType(): self
    {
        return new self('Parameter has no type');
    }

    /**
     * Create an exception for a listener callable without parameters.
     *
     * @return self exception describing the invalid listener signature
     */
    public static function forListenerWithoutParameters(): self
    {
        return new self('Listener has no parameters');
    }

    /**
     * Create an exception for a listener callable whose first parameter has no type.
     *
     * @return self exception describing the missing listener parameter type
     */
    public static function forListenerParameterWithoutType(): self
    {
        return new self('Listener parameter has no type');
    }

    /**
     * Create an exception for a cache invalidation failure.
     *
     * @param list<string> $keys cache keys that could not be invalidated
     *
     * @return self exception describing the cache invalidation failure
     */
    public static function forCacheInvalidationFailure(array $keys): self
    {
        return new self(\sprintf('Failed to invalidate cache keys: "%s".', implode('", "', $keys)));
    }

    /**
     * Create an exception for an unsuccessful webhook response.
     *
     * @param string $uri webhook URI that returned an unsuccessful response
     * @param int $statusCode HTTP status code returned by the webhook endpoint
     *
     * @return self exception describing the webhook publishing failure
     */
    public static function forWebhookRequestFailure(string $uri, int $statusCode): self
    {
        return new self(\sprintf('Webhook request to "%s" failed with status code %d.', $uri, $statusCode));
    }
}
