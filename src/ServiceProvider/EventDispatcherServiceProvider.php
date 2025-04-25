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
        private readonly EventDispatcherContainer $container,
        callable|EventSubscriberInterface|ListenerProviderInterface|string ...$listeners
    ) {
        foreach ($listeners as $listener) {
            $hasAttribute = $this->getReflectionAttributes($listener)->getIterator()->current();

            $collection = match (true) {
                $hasAttribute                                               => PrioritizedListenerProvider::class,
                is_subclass_of($listener, ListenerProviderInterface::class) => ListenerProviderAggregate::class,
                is_subclass_of($listener, EventSubscriberInterface::class)  => EventSubscriberListenerProvider::class,
                default                                                     => ReflectionBasedListenerProvider::class,
            };

            if (ReflectionBasedListenerProvider::class === $collection && !\is_string($listener) && !\is_callable($listener)) {
                throw RuntimeException::forInvalidListenerType($listener);
            }

            $this->listeners[$collection] ??= [];
            $this->listeners[$collection][] = $listener;
        }
    }

    public function getFactories(): array
    {
        $eventDispatcherAlias = $this->getAlias(EventDispatcher::class);

        return [
            EventDispatcherContainer::class => $this->container,

            EventDispatcherInterface::class                 => $eventDispatcherAlias,
            SymfonyContractsEventDispatcherInterface::class => $eventDispatcherAlias,
            SymfonyComponentEventDispatcherInterface::class => $eventDispatcherAlias,
            ListenerProviderInterface::class                => $this->getAlias(ListenerProviderAggregate::class),

            EventDispatcher::class => $this->getFactory(
                EventDispatcher::class,
                ListenerProviderInterface::class
            ),
            ListenerProviderAggregate::class => $this->getFactory(
                ListenerProviderAggregate::class,
                ...$this->listeners[ListenerProviderAggregate::class],
            ),
            PrioritizedListenerProvider::class     => $this->getFactory(PrioritizedListenerProvider::class),
            ReflectionBasedListenerProvider::class => $this->getFactory(ReflectionBasedListenerProvider::class),
            EventSubscriberListenerProvider::class => $this->getFactory(
                EventSubscriberListenerProvider::class,
                ...$this->listeners[EventSubscriberListenerProvider::class],
            ),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ListenerProviderAggregate::class => static function (
                ContainerInterface $container,
                ListenerProviderAggregate $listenerProviderAggregate,
            ): void {
                $listenerProviderAggregate->attach($container->get(PrioritizedListenerProvider::class));
                $listenerProviderAggregate->attach($container->get(ReflectionBasedListenerProvider::class));
                $listenerProviderAggregate->attach($container->get(EventSubscriberListenerProvider::class));
            },
            PrioritizedListenerProvider::class => function (
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
            },
            ReflectionBasedListenerProvider::class => function (
                ContainerInterface $container,
                ReflectionBasedListenerProvider $reflectionBasedListenerProvider,
            ): void {
                foreach ($this->listeners[ReflectionBasedListenerProvider::class] as $listener) {
                    if ($container->has($listener)) {
                        $listener = new LazyListener($container, $listener);
                    }

                    $reflectionBasedListenerProvider->listen($listener);
                }
            },
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

    private function getFactory(string $class, string ...$dependecies): callable
    {
        return static fn (ContainerInterface $container) => new $class(
            ...array_map(
                static fn ($dependency) => \is_string($dependency) ? $container->get($dependency) : $dependency,
                $dependecies,
            ),
        );
    }

    private function getAlias(string $alias): callable
    {
        return static fn (ContainerInterface $container) => $container->get($alias);
    }
}
