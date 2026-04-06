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

namespace FastForward\EventDispatcher\ServiceProvider;

use FastForward\Config\ConfigInterface;
use FastForward\EventDispatcher\ServiceProvider\Extension\ReflectionBasedListenerProviderExtension;
use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\Factory\InvokableFactory;
use FastForward\EventDispatcher\ServiceProvider\Extension\ListenerProviderAggregateExtension;
use FastForward\EventDispatcher\EventDispatcher;
use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
use FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection;
use FastForward\EventDispatcher\ServiceProvider\Extension\EventSubscriberListenerProviderExtension;
use FastForward\EventDispatcher\ServiceProvider\Extension\PrioritizedListenerProviderExtension;
use Interop\Container\ServiceProviderInterface;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider;
use Phly\EventDispatcher\ListenerProvider\ReflectionBasedListenerProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyContractsEventDispatcherInterface;

/**
 * Register the event-dispatcher services exposed by this package.
 */
final class EventDispatcherServiceProvider implements ServiceProviderInterface
{
    /**
     * Return the service factories provided by this library.
     *
     * @return array<class-string, mixed> factory definitions keyed by service identifier
     */
    public function getFactories(): array
    {
        return [
            ListenerProviderInterface::class                => AliasFactory::get(ListenerProviderAggregate::class),
            EventDispatcherInterface::class                 => AliasFactory::get(EventDispatcher::class),
            SymfonyContractsEventDispatcherInterface::class => AliasFactory::get(EventDispatcher::class),

            ListenerProviderAggregate::class => new InvokableFactory(
                ListenerProviderAggregate::class,
                PrioritizedListenerProvider::class,
                ReflectionBasedListenerProvider::class,
                EventSubscriberListenerProvider::class,
            ),

            EventDispatcher::class => new InvokableFactory(
                EventDispatcher::class,
                ListenerProviderInterface::class
            ),

            PrioritizedListenerProvider::class     => new InvokableFactory(PrioritizedListenerProvider::class),
            ReflectionBasedListenerProvider::class => new InvokableFactory(ReflectionBasedListenerProvider::class),
            EventSubscriberListenerProvider::class => new InvokableFactory(EventSubscriberListenerProvider::class),

            ConfiguredListenerProviderCollection::class => new InvokableFactory(
                ConfiguredListenerProviderCollection::class,
                ConfigInterface::class,
            ),
        ];
    }

    /**
     * Return service extensions applied after factory creation.
     *
     * @return array<class-string, object> extension callbacks keyed by service identifier
     */
    public function getExtensions(): array
    {
        return [
            ListenerProviderAggregate::class => new ListenerProviderAggregateExtension(),
            PrioritizedListenerProvider::class => new PrioritizedListenerProviderExtension(),
            ReflectionBasedListenerProvider::class => new ReflectionBasedListenerProviderExtension(),
            EventSubscriberListenerProvider::class => new EventSubscriberListenerProviderExtension(),
        ];
    }

    // /**
    //  * @param string $class
    //  *
    //  * @return IteratorAggregate
    //  */
    // private function getReflectionAttributes(string $class): IteratorAggregate
    // {
    //     return new GeneratorCachingIteratorAggregate(function () use ($class) {
    //         $reflection = new ReflectionClass($class);

    //         $factory = function ($attribute) use ($reflection) {
    //             $instance = $attribute->newInstance();
    //             $instance->method ??= '__invoke';
    //             $instance->event ??= $this->getReflectionEventType($reflection->getMethod($instance->method));

    //             return $instance;
    //         };

    //         yield from array_map($factory, $reflection->getAttributes(AsEventListener::class));

    //         foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    //             $factory = function ($attribute) use ($method) {
    //                 $instance = $attribute->newInstance();
    //                 $instance->method ??= $method->getName();
    //                 $instance->event ??= $this->getReflectionEventType($method);

    //                 return $instance;
    //             };

    //             yield from array_map($factory, $method->getAttributes(AsEventListener::class));
    //         }
    //     });
    // }

    // /**
    //  * @param ReflectionMethod $method
    //  *
    //  * @return string
    //  *
    //  * @throws RuntimeException
    //  */
    // private function getReflectionEventType(ReflectionMethod $method): string
    // {
    //     $parameters = $method->getParameters();
    //     if (empty($parameters)) {
    //         throw RuntimeException::forMethodWithoutParameters();
    //     }

    //     $parameter = $parameters[0];

    //     if (! $parameter->hasType()) {
    //         throw RuntimeException::forMethodParameterWithoutType();
    //     }

    //     return $parameter->getType()
    //         ->getName();
    // }

    // /**
    //  * @param callable|EventSubscriberInterface|ListenerProviderInterface|string $listener
    //  *
    //  * @return string
    //  */
    // private function getListenerProviderType(
    //     callable|EventSubscriberInterface|ListenerProviderInterface|string $listener
    // ): string {
    //     $hasAttribute = $this->getReflectionAttributes($listener)
    //         ->getIterator()
    //         ->current();

    //     $listenerProviderType = match (true) {
    //         $hasAttribute                                               => PrioritizedListenerProvider::class,
    //         is_subclass_of($listener, ListenerProviderInterface::class) => ListenerProviderAggregate::class,
    //         is_subclass_of($listener, EventSubscriberInterface::class)  => EventSubscriberListenerProvider::class,
    //         default                                                     => ReflectionBasedListenerProvider::class,
    //     };

    //     if (ReflectionBasedListenerProvider::class === $listenerProviderType
    //         && ! \is_string($listener)
    //         && ! \is_callable($listener)
    //     ) {
    //         throw RuntimeException::forUnsupportedType($listener);
    //     }

    //     return $listenerProviderType;
    // }

    // /**
    //  * @param ContainerInterface $container
    //  * @param PrioritizedListenerProvider $prioritizedListenerProvider
    //  *
    //  * @return void
    //  */
    // private function extendPrioritizedListenerProvider(
    //     ContainerInterface $container,
    //     PrioritizedListenerProvider $prioritizedListenerProvider,
    // ): void {
    //     foreach ($this->listeners[PrioritizedListenerProvider::class] as $listener) {
    //         $attributes = $this->getReflectionAttributes($listener);

    //         foreach ($attributes as $attribute) {
    //             $priority  = $attribute->priority;
    //             $eventType = $attribute->event;
    //             $method    = $attribute->method;

    //             if (\is_string($listener) && $container->has($listener)) {
    //                 $listener = new LazyListener($container, $listener, $method);
    //             }

    //             $prioritizedListenerProvider->listen($eventType, $listener, $priority);
    //         }
    //     }
    // }

    // /**
    //  * @param ContainerInterface $container
    //  * @param ReflectionBasedListenerProvider $reflectionBasedListenerProvider
    //  *
    //  * @return void
    //  */
    // private function extendReflectionBasedListenerProvider(
    //     ContainerInterface $container,
    //     ReflectionBasedListenerProvider $reflectionBasedListenerProvider,
    // ): void {
    //     foreach ($this->listeners[ReflectionBasedListenerProvider::class] as $listener) {
    //         if ($container->has($listener)) {
    //             $listener = new LazyListener($container, $listener);
    //         }

    //         $reflectionBasedListenerProvider->listen($listener);
    //     }
    // }
}
