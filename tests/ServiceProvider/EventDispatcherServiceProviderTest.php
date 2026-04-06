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

namespace FastForward\EventDispatcher\Tests\ServiceProvider;

use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider;
use Phly\EventDispatcher\ListenerProvider\ReflectionBasedListenerProvider;
use FastForward\Config\ArrayConfig;
use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\Factory\InvokableFactory;
use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredPrioritizedListener;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredReflectionListener;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use FastForward\EventDispatcher\ServiceProvider\Extension\EventSubscriberListenerProviderExtension;
use FastForward\EventDispatcher\ServiceProvider\Extension\ListenerProviderAggregateExtension;
use FastForward\EventDispatcher\ServiceProvider\Extension\PrioritizedListenerProviderExtension;
use FastForward\EventDispatcher\ServiceProvider\Extension\ReflectionBasedListenerProviderExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function FastForward\Container\container;

#[CoversClass(EventDispatcherServiceProvider::class)]
#[UsesClass(ConfiguredListenerProviderCollection::class)]
#[UsesClass(ConfiguredPrioritizedListener::class)]
#[UsesClass(ConfiguredReflectionListener::class)]
#[UsesClass(EventSubscriberListenerProvider::class)]
#[UsesClass(EventSubscriberListenerProviderExtension::class)]
#[UsesClass(ListenerProviderAggregateExtension::class)]
#[UsesClass(PrioritizedListenerProviderExtension::class)]
#[UsesClass(ReflectionBasedListenerProviderExtension::class)]
final class EventDispatcherServiceProviderTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testGetFactoriesWillExposeExpectedServiceDefinitions(): void
    {
        $factories = (new EventDispatcherServiceProvider())->getFactories();

        self::assertArrayHasKey(ListenerProviderInterface::class, $factories);
        self::assertInstanceOf(AliasFactory::class, $factories[ListenerProviderInterface::class]);
        self::assertInstanceOf(InvokableFactory::class, $factories[ConfiguredListenerProviderCollection::class]);
    }

    /**
     * @return void
     */
    #[Test]
    public function testGetExtensionsWillExposeExpectedServiceExtensions(): void
    {
        $extensions = (new EventDispatcherServiceProvider())->getExtensions();

        self::assertCount(4, $extensions);
        self::assertInstanceOf(
            ListenerProviderAggregateExtension::class,
            $extensions[ListenerProviderAggregate::class]
        );
        self::assertInstanceOf(
            PrioritizedListenerProviderExtension::class,
            $extensions[PrioritizedListenerProvider::class]
        );
        self::assertInstanceOf(
            ReflectionBasedListenerProviderExtension::class,
            $extensions[ReflectionBasedListenerProvider::class]
        );
        self::assertInstanceOf(
            EventSubscriberListenerProviderExtension::class,
            $extensions[EventSubscriberListenerProvider::class]
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function testConfiguredListenersAreRegisteredInMatchingProviders(): void
    {
        $container = container(
            new ArrayConfig([
                ListenerProviderInterface::class => [
                    AttributeListener::class,
                    InvokableListener::class,
                    SubscriberListener::class,
                    DelegatingListenerProvider::class,
                ],
            ]),
            new EventDispatcherServiceProvider(),
        );

        $listenerProvider = $container->get(ListenerProviderInterface::class);
        $event            = new TestEvent();

        foreach ($listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['attribute', 'reflection', 'subscriber', 'provider'], $event->calls);
    }
}

final class TestEvent
{
    /**
     * @var list<string>
     */
    public array $calls = [];
}

final class AttributeListener
{
    /**
     * @param TestEvent $event
     *
     * @return void
     */
    #[AsEventListener(priority: 20)]
    public function onTest(TestEvent $event): void
    {
        $event->calls[] = 'attribute';
    }
}

final class InvokableListener
{
    /**
     * @param TestEvent $event
     *
     * @return void
     */
    public function __invoke(TestEvent $event): void
    {
        $event->calls[] = 'reflection';
    }
}

final class SubscriberListener implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TestEvent::class => ['onTest', 10],
        ];
    }

    /**
     * @param TestEvent $event
     *
     * @return void
     */
    public function onTest(TestEvent $event): void
    {
        $event->calls[] = 'subscriber';
    }
}

final class DelegatingListenerProvider implements ListenerProviderInterface
{
    /**
     * @param object $event
     *
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        if (! $event instanceof TestEvent) {
            return [];
        }

        yield static function (TestEvent $event): void {
            $event->calls[] = 'provider';
        };
    }
}
