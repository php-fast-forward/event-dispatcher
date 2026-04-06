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

namespace FastForward\EventDispatcher\Tests\ServiceProvider\Extension;

use ReflectionProperty;
use stdClass;
use FastForward\EventDispatcher\Exception\RuntimeException;
use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use FastForward\EventDispatcher\ServiceProvider\Extension\EventSubscriberListenerProviderExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

#[CoversClass(EventSubscriberListenerProviderExtension::class)]
#[UsesClass(ConfiguredListenerProviderCollection::class)]
#[UsesClass(EventSubscriberListenerProvider::class)]
#[UsesClass(RuntimeException::class)]
final class EventSubscriberListenerProviderExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRegisterResolvedSubscribers(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith('eventSubscribers', [EventSubscriberExtensionSubscriber::class]);
        $subscriber = new EventSubscriberExtensionSubscriber();

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(EventSubscriberExtensionSubscriber::class)->willReturn(true);
        $container->get(EventSubscriberExtensionSubscriber::class)->willReturn($subscriber);

        $provider = new EventSubscriberListenerProvider();

        (new EventSubscriberListenerProviderExtension())($container->reveal(), $provider);

        $event = new EventSubscriberExtensionEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['subscriber'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillAcceptAlreadyInstantiatedSubscribers(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith('eventSubscribers', [new EventSubscriberExtensionSubscriber()]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);

        $provider = new EventSubscriberListenerProvider();

        (new EventSubscriberListenerProviderExtension())($container->reveal(), $provider);

        $event = new EventSubscriberExtensionEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['subscriber'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRejectResolvedValuesThatAreNotSubscribers(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith('eventSubscribers', [EventSubscriberExtensionSubscriber::class]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(EventSubscriberExtensionSubscriber::class)->willReturn(true);
        $container->get(EventSubscriberExtensionSubscriber::class)->willReturn(new stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported listener type: "stdClass".');

        (new EventSubscriberListenerProviderExtension())($container->reveal(), new EventSubscriberListenerProvider());
    }

    /**
     * @param string $property
     * @param array $values
     *
     * @return ConfiguredListenerProviderCollection
     */
    private function collectionWith(string $property, array $values): ConfiguredListenerProviderCollection
    {
        $collection = new ConfiguredListenerProviderCollection('listeners');
        $reflection = new ReflectionProperty($collection, $property);
        $reflection->setValue($collection, $values);

        return $collection;
    }
}

final class EventSubscriberExtensionEvent
{
    public array $calls = [];
}

final class EventSubscriberExtensionSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EventSubscriberExtensionEvent::class => 'handle',
        ];
    }

    /**
     * @param EventSubscriberExtensionEvent $event
     *
     * @return void
     */
    public function handle(EventSubscriberExtensionEvent $event): void
    {
        $event->calls[] = 'subscriber';
    }
}
