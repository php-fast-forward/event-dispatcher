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
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function FastForward\Container\container;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class PaymentReceived
{
    /**
     * @param string $invoiceId
     * @param float $amount
     */
    public function __construct(
        public string $invoiceId,
        public float $amount,
    ) {}
}

final class BillingSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'billing.payment_received' => 'onPaymentReceived',
        ];
    }

    /**
     * @param PaymentReceived $event
     *
     * @return void
     */
    public function onPaymentReceived(PaymentReceived $event): void
    {
        echo 'Payment confirmed for invoice ' . $event->invoiceId
            . ' with amount $' . number_format($event->amount, 2, '.', ',')
            . \PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [EventDispatcherServiceProvider::class],
    ListenerProviderInterface::class => [BillingSubscriber::class],
]);

$container = container($config);
$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new PaymentReceived('INV-2026-0001', 199.90), 'billing.payment_received');
