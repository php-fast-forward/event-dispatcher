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
use FastForward\EventDispatcher\Exception\RuntimeException;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredPrioritizedListener;
use FastForward\EventDispatcher\ServiceProvider\Extension\PrioritizedListenerProviderExtension;
use Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

#[CoversClass(PrioritizedListenerProviderExtension::class)]
#[UsesClass(ConfiguredListenerProviderCollection::class)]
#[UsesClass(ConfiguredPrioritizedListener::class)]
#[UsesClass(RuntimeException::class)]
final class PrioritizedListenerProviderExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRegisterResolvedLazyListeners(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith([
            new ConfiguredPrioritizedListener(
                PrioritizedExtensionListenerService::class,
                PrioritizedExtensionEvent::class,
                'handle',
                20,
            ),
        ]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(PrioritizedExtensionListenerService::class)->willReturn(true);
        $container->get(PrioritizedExtensionListenerService::class)->willReturn(
            new PrioritizedExtensionListenerService()
        );

        $provider = new PrioritizedListenerProvider();

        (new PrioritizedListenerProviderExtension())($container->reveal(), $provider);

        $event = new PrioritizedExtensionEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['service'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRegisterCallableInstanceMethods(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith([
            new ConfiguredPrioritizedListener(
                new PrioritizedExtensionObjectListener(),
                PrioritizedExtensionEvent::class,
                'handle',
                10,
            ),
        ]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);

        $provider = new PrioritizedListenerProvider();

        (new PrioritizedListenerProviderExtension())($container->reveal(), $provider);

        $event = new PrioritizedExtensionEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['object'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRejectNonCallableListeners(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith([
            new ConfiguredPrioritizedListener(
                PrioritizedExtensionListenerService::class,
                PrioritizedExtensionEvent::class,
                'handle',
                5,
            ),
        ]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(PrioritizedExtensionListenerService::class)->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported listener type: "array".');

        (new PrioritizedListenerProviderExtension())($container->reveal(), new PrioritizedListenerProvider());
    }

    /**
     * @param list<ConfiguredPrioritizedListener> $listeners
     */
    private function collectionWith(array $listeners): ConfiguredListenerProviderCollection
    {
        $collection = new ConfiguredListenerProviderCollection('listeners');
        $reflection = new ReflectionProperty($collection, 'prioritizedListeners');
        $reflection->setValue($collection, $listeners);

        return $collection;
    }
}

final class PrioritizedExtensionEvent
{
    public array $calls = [];
}

final class PrioritizedExtensionListenerService
{
    /**
     * @param PrioritizedExtensionEvent $event
     *
     * @return void
     */
    public function handle(PrioritizedExtensionEvent $event): void
    {
        $event->calls[] = 'service';
    }
}

final class PrioritizedExtensionObjectListener
{
    /**
     * @param PrioritizedExtensionEvent $event
     *
     * @return void
     */
    public function handle(PrioritizedExtensionEvent $event): void
    {
        $event->calls[] = 'object';
    }
}
