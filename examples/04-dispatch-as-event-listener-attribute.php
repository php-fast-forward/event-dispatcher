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
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function FastForward\Container\container;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class OrderConfirmed
{
    /**
     * @param string $orderNumber
     */
    public function __construct(
        public string $orderNumber
    ) {}
}

final class ReserveStockListener
{
    /**
     * @param OrderConfirmed $event
     *
     * @return void
     */
    #[AsEventListener(priority: 100)]
    public function __invoke(OrderConfirmed $event): void
    {
        echo 'Reserving stock for the order ' . $event->orderNumber . \PHP_EOL;
    }
}

final class NotifyOperationsListener
{
    /**
     * @param OrderConfirmed $event
     *
     * @return void
     */
    #[AsEventListener(priority: 10)]
    public function __invoke(OrderConfirmed $event): void
    {
        echo 'Notifying operations about the order ' . $event->orderNumber . \PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [EventDispatcherServiceProvider::class],
    ListenerProviderInterface::class => [NotifyOperationsListener::class, ReserveStockListener::class],
]);

$container = container($config);
$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new OrderConfirmed('PED-2026-0042'));
