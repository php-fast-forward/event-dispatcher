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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function FastForward\Container\container;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class SubscriptionActivated
{
    /**
     * @param string $customerEmail
     * @param string $plan
     */
    public function __construct(
        public string $customerEmail,
        public string $plan,
    ) {}
}

final class SubscriptionLifecycleSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SubscriptionActivated::class => [['provisionAccess', 20], ['sendReceipt', 10]],
        ];
    }

    /**
     * @param SubscriptionActivated $event
     *
     * @return void
     */
    public function provisionAccess(SubscriptionActivated $event): void
    {
        echo 'Provisioning plan access for ' . $event->plan . \PHP_EOL;
    }

    /**
     * @param SubscriptionActivated $event
     *
     * @return void
     */
    public function sendReceipt(SubscriptionActivated $event): void
    {
        echo 'Sending receipt to ' . $event->customerEmail . \PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [EventDispatcherServiceProvider::class],
    ListenerProviderInterface::class => [SubscriptionLifecycleSubscriber::class],
]);

$container = container($config);
$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new SubscriptionActivated('github@mentordosnerds.com', 'pro'));
