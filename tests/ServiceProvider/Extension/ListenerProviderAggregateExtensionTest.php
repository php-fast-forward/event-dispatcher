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
use FastForward\EventDispatcher\ServiceProvider\Extension\ListenerProviderAggregateExtension;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

#[CoversClass(ListenerProviderAggregateExtension::class)]
#[UsesClass(ConfiguredListenerProviderCollection::class)]
#[UsesClass(RuntimeException::class)]
final class ListenerProviderAggregateExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillAttachResolvedListenerProviders(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith('listenerProviders', [AggregateExtensionListenerProvider::class]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(AggregateExtensionListenerProvider::class)->willReturn(true);
        $container->get(AggregateExtensionListenerProvider::class)->willReturn(
            new AggregateExtensionListenerProvider()
        );

        $aggregate = new ListenerProviderAggregate();

        (new ListenerProviderAggregateExtension())($container->reveal(), $aggregate);

        $event = new AggregateExtensionEvent();

        foreach ($aggregate->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['provider'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillAcceptAlreadyInstantiatedListenerProviders(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith('listenerProviders', [new AggregateExtensionListenerProvider()]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);

        $aggregate = new ListenerProviderAggregate();

        (new ListenerProviderAggregateExtension())($container->reveal(), $aggregate);

        $event = new AggregateExtensionEvent();

        foreach ($aggregate->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['provider'], $event->calls);
    }

    /**
     * @return void
     */
    #[Test]
    public function testInvokeWillRejectUnresolvedListenerProviderStrings(): void
    {
        $container  = $this->prophesize(ContainerInterface::class);
        $collection = $this->collectionWith('listenerProviders', [AggregateExtensionListenerProvider::class]);

        $container->get(ConfiguredListenerProviderCollection::class)->willReturn($collection);
        $container->has(AggregateExtensionListenerProvider::class)->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported listener type: "string".');

        (new ListenerProviderAggregateExtension())($container->reveal(), new ListenerProviderAggregate());
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

final class AggregateExtensionEvent
{
    public array $calls = [];
}

final class AggregateExtensionListenerProvider implements ListenerProviderInterface
{
    /**
     * @param object $event
     *
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        if (! $event instanceof AggregateExtensionEvent) {
            return [];
        }

        yield static function (AggregateExtensionEvent $event): void {
            $event->calls[] = 'provider';
        };
    }
}
