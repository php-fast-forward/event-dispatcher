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

use ReflectionMethod;
use ReflectionProperty;
use FastForward\EventDispatcher\Exception\RuntimeException;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredReflectionListener;
use FastForward\EventDispatcher\ServiceProvider\Extension\ReflectionBasedListenerProviderExtension;
use Phly\EventDispatcher\ListenerProvider\ReflectionBasedListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

#[CoversClass(ReflectionBasedListenerProviderExtension::class)]
#[UsesClass(ConfiguredListenerProviderCollection::class)]
#[UsesClass(ConfiguredReflectionListener::class)]
#[UsesClass(RuntimeException::class)]
final class ReflectionBasedListenerProviderExtensionTest extends TestCase
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
            new ConfiguredReflectionListener(
                ReflectionExtensionListenerService::class,
                ReflectionExtensionEvent::class,
            ),
        ]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(ReflectionExtensionListenerService::class)->willReturn(true);
        $container->get(ReflectionExtensionListenerService::class)->willReturn(
            new ReflectionExtensionListenerService()
        );

        $provider = new ReflectionBasedListenerProvider();

        (new ReflectionBasedListenerProviderExtension())($container->reveal(), $provider);

        $event = new ReflectionExtensionEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['service'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRegisterDirectCallableListeners(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith([
            new ConfiguredReflectionListener(
                new ReflectionExtensionInvokableListener(),
                ReflectionExtensionEvent::class,
            ),
        ]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);

        $provider = new ReflectionBasedListenerProvider();

        (new ReflectionBasedListenerProviderExtension())($container->reveal(), $provider);

        $event = new ReflectionExtensionEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['object'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRejectUnresolvedNonCallableStrings(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith([
            new ConfiguredReflectionListener(
                ReflectionExtensionListenerService::class,
                ReflectionExtensionEvent::class,
            ),
        ]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(ReflectionExtensionListenerService::class)->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported listener type: "string".');

        (new ReflectionBasedListenerProviderExtension())($container->reveal(), new ReflectionBasedListenerProvider());
    }

    /**
     * @return void
     */
    #[Test]
    public function testResolveCallableWillAcceptAnExplicitMethodName(): void
    {
        $extension  = new ReflectionBasedListenerProviderExtension();
        $reflection = new ReflectionMethod($extension, 'resolveCallable');
        $callable   = $reflection->invoke(
            $extension,
            $this->prophesize(ContainerInterface::class)->reveal(),
            new ReflectionExtensionMethodListener(),
            'handle',
        );

        $event = new ReflectionExtensionEvent();
        $callable($event);

        self::assertSame(['method'], $event->calls);
    }

    /**
     * @param list<ConfiguredReflectionListener> $listeners
     */
    private function collectionWith(array $listeners): ConfiguredListenerProviderCollection
    {
        $collection = new ConfiguredListenerProviderCollection('listeners');
        $reflection = new ReflectionProperty($collection, 'reflectionListeners');
        $reflection->setValue($collection, $listeners);

        return $collection;
    }
}

final class ReflectionExtensionEvent
{
    public array $calls = [];
}

final class ReflectionExtensionListenerService
{
    /**
     * @param ReflectionExtensionEvent $event
     *
     * @return void
     */
    public function __invoke(ReflectionExtensionEvent $event): void
    {
        $event->calls[] = 'service';
    }
}

final class ReflectionExtensionInvokableListener
{
    /**
     * @param ReflectionExtensionEvent $event
     *
     * @return void
     */
    public function __invoke(ReflectionExtensionEvent $event): void
    {
        $event->calls[] = 'object';
    }
}

final class ReflectionExtensionMethodListener
{
    /**
     * @param ReflectionExtensionEvent $event
     *
     * @return void
     */
    public function handle(ReflectionExtensionEvent $event): void
    {
        $event->calls[] = 'method';
    }
}
