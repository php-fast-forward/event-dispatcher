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

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\EventDispatcher\ListenerProvider\LogEventListenerProvider;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function FastForward\Container\container;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class UserRegistered
{
    /**
     * @param string $email
     */
    public function __construct(
        public string $email
    ) {}
}

final class StdoutLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        echo '[' . strtoupper((string) $level) . '] ' . $message . \PHP_EOL;
        echo 'Event class: ' . $context['event_class'] . \PHP_EOL;

        if (isset($context['event_name'])) {
            echo 'Event name: ' . $context['event_name'] . \PHP_EOL;
        }

        if (isset($context['wrapped_event_class'])) {
            echo 'Wrapped event class: ' . $context['wrapped_event_class'] . \PHP_EOL;
        }

        echo \PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [EventDispatcherServiceProvider::class],
    ListenerProviderInterface::class => [LogEventListenerProvider::class],
]);

$container = container(
    $config,
    new ArrayServiceProvider([
        LoggerInterface::class => static fn(): LoggerInterface => new StdoutLogger(),
    ]),
);

$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new UserRegistered('github@mentordosnerds.com'), 'users.registered');
