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

namespace FastForward\EventDispatcher\Tests\ServiceProvider\Configuration;

use stdClass;
use FastForward\Config\ArrayConfig;
use FastForward\EventDispatcher\Exception\RuntimeException;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredPrioritizedListener;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredReflectionListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

#[CoversClass(ConfiguredListenerProviderCollection::class)]
#[UsesClass(ConfiguredPrioritizedListener::class)]
#[UsesClass(ConfiguredReflectionListener::class)]
#[UsesClass(RuntimeException::class)]
final class ConfiguredListenerProviderCollectionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function testStringConfigWillProduceEmptyCollections(): void
    {
        $collection = new ConfiguredListenerProviderCollection('listeners');

        self::assertSame([], $collection->listenerProviders());
        self::assertSame([], $collection->eventSubscribers());
        self::assertSame([], $collection->reflectionListeners());
        self::assertSame([], $collection->prioritizedListeners());
    }

    /**
     * @return void
     */
    #[Test]
    public function testMissingListenerProviderConfigWillProduceEmptyCollections(): void
    {
        $collection = new ConfiguredListenerProviderCollection(new ArrayConfig([
            'other' => [],
        ]));

        self::assertSame([], $collection->listenerProviders());
        self::assertSame([], $collection->eventSubscribers());
        self::assertSame([], $collection->reflectionListeners());
        self::assertSame([], $collection->prioritizedListeners());
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillClassifyConfiguredListenersAndInferMetadata(): void
    {
        $closure      = static function (ConfiguredCollectionEvent $event): void {};
        $arrayListener = [new ConfiguredCollectionArrayCallableListener(), 'handle'];
        $objectListener = new ConfiguredCollectionInvokableObjectListener();

        $collection = new ConfiguredListenerProviderCollection(new ArrayConfig([
            ListenerProviderInterface::class => [
                ConfiguredCollectionAttributedListener::class,
                ConfiguredCollectionListenerProvider::class,
                ConfiguredCollectionSubscriber::class,
                $closure,
                ConfiguredCollectionStaticCallableListener::class . '::handle',
                $arrayListener,
                $objectListener,
                __NAMESPACE__ . '\\configuredCollectionFunctionListener',
                ConfiguredCollectionInvokableStringListener::class,
            ],
        ]));

        self::assertSame([ConfiguredCollectionListenerProvider::class], $collection->listenerProviders());
        self::assertSame([ConfiguredCollectionSubscriber::class], $collection->eventSubscribers());

        $prioritizedListeners = $collection->prioritizedListeners();

        self::assertCount(2, $prioritizedListeners);
        self::assertSame(ConfiguredCollectionAttributedListener::class, $prioritizedListeners[0]->listener);
        self::assertSame(ConfiguredCollectionEvent::class, $prioritizedListeners[0]->eventType);
        self::assertSame('__invoke', $prioritizedListeners[0]->method);
        self::assertSame(30, $prioritizedListeners[0]->priority);
        self::assertSame(ConfiguredCollectionAttributedListener::class, $prioritizedListeners[1]->listener);
        self::assertSame(ConfiguredCollectionOtherEvent::class, $prioritizedListeners[1]->eventType);
        self::assertSame('onOther', $prioritizedListeners[1]->method);
        self::assertSame(5, $prioritizedListeners[1]->priority);

        $reflectionListeners = $collection->reflectionListeners();

        self::assertCount(6, $reflectionListeners);
        self::assertSame($closure, $reflectionListeners[0]->listener);
        self::assertSame(ConfiguredCollectionEvent::class, $reflectionListeners[0]->eventType);
        self::assertSame(
            ConfiguredCollectionStaticCallableListener::class . '::handle',
            $reflectionListeners[1]->listener
        );
        self::assertSame(ConfiguredCollectionEvent::class, $reflectionListeners[1]->eventType);
        self::assertSame($arrayListener, $reflectionListeners[2]->listener);
        self::assertSame(ConfiguredCollectionEvent::class, $reflectionListeners[2]->eventType);
        self::assertSame($objectListener, $reflectionListeners[3]->listener);
        self::assertSame(ConfiguredCollectionEvent::class, $reflectionListeners[3]->eventType);
        self::assertSame(__NAMESPACE__ . '\\configuredCollectionFunctionListener', $reflectionListeners[4]->listener);
        self::assertSame(ConfiguredCollectionEvent::class, $reflectionListeners[4]->eventType);
        self::assertSame(ConfiguredCollectionInvokableStringListener::class, $reflectionListeners[5]->listener);
        self::assertSame(ConfiguredCollectionEvent::class, $reflectionListeners[5]->eventType);
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillRejectUnsupportedConfiguredListenerTypes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported listener type: "stdClass".');

        new ConfiguredListenerProviderCollection(new ArrayConfig([
            ListenerProviderInterface::class => [new stdClass()],
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillRejectAttributedListenersWithoutParameters(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Method has no parameters');

        new ConfiguredListenerProviderCollection(new ArrayConfig([
            ListenerProviderInterface::class => [ConfiguredCollectionAttributedWithoutParameters::class],
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillRejectAttributedListenersWithoutTypedParameters(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter has no type');

        new ConfiguredListenerProviderCollection(new ArrayConfig([
            ListenerProviderInterface::class => [ConfiguredCollectionAttributedWithoutType::class],
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillRejectCallableListenersWithoutParameters(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Listener has no parameters');

        new ConfiguredListenerProviderCollection(new ArrayConfig([
            ListenerProviderInterface::class => [static function (): void {}],
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillRejectCallableListenersWithoutTypedParameters(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Listener parameter has no type');

        new ConfiguredListenerProviderCollection(new ArrayConfig([
            ListenerProviderInterface::class => [static function ($event): void {}],
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function testConstructorWillRejectUnknownCallableStrings(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported listener type: "string".');

        new ConfiguredListenerProviderCollection(new ArrayConfig([
            ListenerProviderInterface::class => ['missing-listener'],
        ]));
    }
}

final class ConfiguredCollectionEvent {}

final class ConfiguredCollectionOtherEvent {}

final class ConfiguredCollectionListenerProvider implements ListenerProviderInterface
{
    /**
     * @param object $event
     *
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        return [];
    }
}

final class ConfiguredCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfiguredCollectionEvent::class => 'onEvent',
        ];
    }

    /**
     * @param ConfiguredCollectionEvent $event
     *
     * @return bool
     */
    public function onEvent(ConfiguredCollectionEvent $event): bool
    {
        return $event instanceof ConfiguredCollectionEvent;
    }
}

#[AsEventListener(priority: 30)]
final class ConfiguredCollectionAttributedListener
{
    /**
     * @param ConfiguredCollectionEvent $event
     *
     * @return bool
     */
    public function __invoke(ConfiguredCollectionEvent $event): bool
    {
        return $event instanceof ConfiguredCollectionEvent;
    }

    /**
     * @param ConfiguredCollectionOtherEvent $event
     *
     * @return bool
     */
    #[AsEventListener(priority: 5)]
    public function onOther(ConfiguredCollectionOtherEvent $event): bool
    {
        return $event instanceof ConfiguredCollectionOtherEvent;
    }
}

#[AsEventListener]
final class ConfiguredCollectionAttributedWithoutParameters
{
    /**
     * @return bool
     */
    public function __invoke(): bool
    {
        return true;
    }
}

#[AsEventListener]
final class ConfiguredCollectionAttributedWithoutType
{
    /**
     * @param mixed $event
     *
     * @return bool
     */
    public function __invoke($event): bool
    {
        return null !== $event;
    }
}

final class ConfiguredCollectionStaticCallableListener
{
    /**
     * @param ConfiguredCollectionEvent $event
     *
     * @return bool
     */
    public static function handle(ConfiguredCollectionEvent $event): bool
    {
        return $event instanceof ConfiguredCollectionEvent;
    }
}

final class ConfiguredCollectionArrayCallableListener
{
    /**
     * @param ConfiguredCollectionEvent $event
     *
     * @return bool
     */
    public function handle(ConfiguredCollectionEvent $event): bool
    {
        return $event instanceof ConfiguredCollectionEvent;
    }
}

final class ConfiguredCollectionInvokableObjectListener
{
    /**
     * @param ConfiguredCollectionEvent $event
     *
     * @return bool
     */
    public function __invoke(ConfiguredCollectionEvent $event): bool
    {
        return $event instanceof ConfiguredCollectionEvent;
    }
}

final class ConfiguredCollectionInvokableStringListener
{
    /**
     * @param ConfiguredCollectionEvent $event
     *
     * @return bool
     */
    public function __invoke(ConfiguredCollectionEvent $event): bool
    {
        return $event instanceof ConfiguredCollectionEvent;
    }
}

function configuredCollectionFunctionListener(ConfiguredCollectionEvent $event): bool
{
    return $event instanceof ConfiguredCollectionEvent;
}
