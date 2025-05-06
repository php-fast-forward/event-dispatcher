<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/event-dispatcher.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @link      https://github.com/php-fast-forward/event-dispatcher
 * @copyright Copyright (c) 2025 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\EventDispatcher\ServiceProvider;

use FastForward\Container\Factory\AliasFactory;
use FastForward\Container\Factory\InvokableFactory;
use FastForward\EventDispatcher\Container\EventDispatcherContainer;
use FastForward\EventDispatcher\EventDispatcher;
use FastForward\EventDispatcher\Exception\RuntimeException;
use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
use FastForward\Iterator\GeneratorCachingIteratorAggregate;
use Interop\Container\ServiceProviderInterface;
use Phly\EventDispatcher\LazyListener;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider;
use Phly\EventDispatcher\ListenerProvider\ReflectionBasedListenerProvider;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyComponentEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyContractsEventDispatcherInterface;

final class EventDispatcherServiceProvider implements ServiceProviderInterface
{
    private array $listeners = [];

    public function __construct(
        callable|EventSubscriberInterface|ListenerProviderInterface|string ...$listeners
    ) {
        foreach ($listeners as $listener) {
            $listenerProviderType = $this->getListenerProviderType($listener);

            $this->listeners[$listenerProviderType] ??= [];
            $this->listeners[$listenerProviderType][] = $listener;
        }
    }

    public function getFactories(): array
    {
        return [
            EventDispatcherInterface::class                 => AliasFactory::get(EventDispatcher::class),
            SymfonyContractsEventDispatcherInterface::class => AliasFactory::get(EventDispatcher::class),
            SymfonyComponentEventDispatcherInterface::class => AliasFactory::get(EventDispatcher::class),
            ListenerProviderInterface::class                => AliasFactory::get(ListenerProviderAggregate::class),

            EventDispatcher::class => new InvokableFactory(
                EventDispatcher::class,
                ListenerProviderInterface::class
            ),
            ListenerProviderAggregate::class => new InvokableFactory(
                ListenerProviderAggregate::class,
                ...$this->listeners[ListenerProviderAggregate::class],
            ),
            PrioritizedListenerProvider::class     => new InvokableFactory(PrioritizedListenerProvider::class),
            ReflectionBasedListenerProvider::class => new InvokableFactory(ReflectionBasedListenerProvider::class),
            EventSubscriberListenerProvider::class => new InvokableFactory(
                EventSubscriberListenerProvider::class,
                ...$this->listeners[EventSubscriberListenerProvider::class],
            ),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ListenerProviderAggregate::class       => [$this, 'extendListenerProviderAggregate'],
            PrioritizedListenerProvider::class     => [$this, 'extendPrioritizedListenerProvider'],
            ReflectionBasedListenerProvider::class => [$this, 'extendReflectionBasedListenerProvider'],
        ];
    }

    private function getReflectionAttributes(string $class): \IteratorAggregate
    {
        return new GeneratorCachingIteratorAggregate(function () use ($class) {
            $reflection = new \ReflectionClass($class);

            $factory = function ($attribute) use ($reflection) {
                $instance = $attribute->newInstance();
                $instance->method ??= '__invoke';
                $instance->event ??= $this->getReflectionEventType($reflection->getMethod($instance->method));

                return $instance;
            };

            yield from array_map($factory, $reflection->getAttributes(AsEventListener::class));

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $factory = function ($attribute) use ($method) {
                    $instance = $attribute->newInstance();
                    $instance->method ??= $method->getName();
                    $instance->event ??= $this->getReflectionEventType($method);

                    return $instance;
                };

                yield from array_map($factory, $method->getAttributes(AsEventListener::class));
            }
        });
    }

    private function getReflectionEventType(\ReflectionMethod $method): string
    {
        $parameters = $method->getParameters();
        if (empty($parameters)) {
            throw new \RuntimeException('Method has no parameters');
        }

        $parameter = $parameters[0];

        if (!$parameter->hasType()) {
            throw new \RuntimeException('Parameter has no type');
        }

        return $parameter->getType()->getName();
    }

    private function getListenerProviderType(
        callable|EventSubscriberInterface|ListenerProviderInterface|string $listener
    ): string {
        $hasAttribute = $this->getReflectionAttributes($listener)->getIterator()->current();

        $listenerProviderType = match (true) {
            $hasAttribute                                               => PrioritizedListenerProvider::class,
            is_subclass_of($listener, ListenerProviderInterface::class) => ListenerProviderAggregate::class,
            is_subclass_of($listener, EventSubscriberInterface::class)  => EventSubscriberListenerProvider::class,
            default                                                     => ReflectionBasedListenerProvider::class,
        };

        if (ReflectionBasedListenerProvider::class === $listenerProviderType
            && !\is_string($listener)
            && !\is_callable($listener)
        ) {
            throw RuntimeException::forUnsupportedType($listener);
        }

        return $listenerProviderType;
    }

    private function extendListenerProviderAggregate(
        ContainerInterface $container,
        ListenerProviderAggregate $listenerProviderAggregate,
    ): void {
        $listenerProviderAggregate->attach($container->get(PrioritizedListenerProvider::class));
        $listenerProviderAggregate->attach($container->get(ReflectionBasedListenerProvider::class));
        $listenerProviderAggregate->attach($container->get(EventSubscriberListenerProvider::class));
    }

    private function extendPrioritizedListenerProvider(
        ContainerInterface $container,
        PrioritizedListenerProvider $prioritizedListenerProvider,
    ): void {
        foreach ($this->listeners[PrioritizedListenerProvider::class] as $listener) {
            $attributes = $this->getReflectionAttributes($listener);

            foreach ($attributes as $attribute) {
                $priority  = $attribute->priority;
                $eventType = $attribute->event;
                $method    = $attribute->method;

                if (\is_string($listener) && $container->has($listener)) {
                    $listener = new LazyListener($container, $listener, $method);
                }

                $prioritizedListenerProvider->listen($eventType, $listener, $priority);
            }
        }
    }

    private function extendReflectionBasedListenerProvider(
        ContainerInterface $container,
        ReflectionBasedListenerProvider $reflectionBasedListenerProvider,
    ): void {
        foreach ($this->listeners[ReflectionBasedListenerProvider::class] as $listener) {
            if ($container->has($listener)) {
                $listener = new LazyListener($container, $listener);
            }

            $reflectionBasedListenerProvider->listen($listener);
        }
    }
}
