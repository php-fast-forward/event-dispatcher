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
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

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

final class SendWelcomeEmailListener
{
    /**
     * @param UserRegistered $event
     *
     * @return void
     */
    public function __invoke(UserRegistered $event): void
    {
        echo 'Sending welcome email to ' . $event->email . \PHP_EOL;
    }
}

$config = new ArrayConfig([
    ListenerProviderInterface::class => [SendWelcomeEmailListener::class],
]);

$container = container($config, EventDispatcherServiceProvider::class);
$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new UserRegistered('github@mentordosnerds.com'));
