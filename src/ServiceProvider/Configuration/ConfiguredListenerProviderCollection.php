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

namespace FastForward\EventDispatcher\ServiceProvider\Configuration;

use ReflectionException;
use Closure;
use FastForward\Config\ConfigInterface;
use FastForward\EventDispatcher\Exception\RuntimeException;
use Psr\EventDispatcher\ListenerProviderInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Classify configured listeners by the provider strategy they require.
 *
 * @internal
 */
final class ConfiguredListenerProviderCollection
{
    /**
     * Listener providers declared directly in configuration.
     *
     * @var list<ListenerProviderInterface|string>
     */
    private array $listenerProviders = [];

    /**
     * Event subscribers declared in configuration.
     *
     * @var list<EventSubscriberInterface|string>
     */
    private array $eventSubscribers = [];

    /**
     * Callables that should be registered with the reflection-based provider.
     *
     * @var list<ConfiguredReflectionListener>
     */
    private array $reflectionListeners = [];

    /**
     * Attribute-based listeners that carry an explicit priority.
     *
     * @var list<ConfiguredPrioritizedListener>
     */
    private array $prioritizedListeners = [];

    /**
     * Build the configured listener collections from application configuration.
     *
     * @param string|ConfigInterface $config configuration source or container key
     *
     * @throws RuntimeException thrown when a configured listener cannot be classified
     * @throws ReflectionException thrown when reflective listener inspection fails
     */
    public function __construct(string|ConfigInterface $config)
    {
        if (\is_string($config) || ! $config->has(ListenerProviderInterface::class)) {
            return;
        }

        $listeners = $config->get(ListenerProviderInterface::class);

        foreach ($listeners as $listener) {
            $attributes = $this->getReflectionAttributes($listener);

            if ([] !== $attributes) {
                foreach ($attributes as $attribute) {
                    $this->prioritizedListeners[] = new ConfiguredPrioritizedListener(
                        $listener,
                        $attribute->event,
                        $attribute->method,
                        $attribute->priority,
                    );
                }

                continue;
            }

            if (is_subclass_of($listener, ListenerProviderInterface::class)) {
                $this->listenerProviders[] = $listener;

                continue;
            }

            if (is_subclass_of($listener, EventSubscriberInterface::class)) {
                $this->eventSubscribers[] = $listener;

                continue;
            }

            if (\is_string($listener) || \is_callable($listener)) {
                $this->reflectionListeners[] = new ConfiguredReflectionListener(
                    $listener,
                    $this->getCallableEventType($listener),
                );

                continue;
            }

            throw RuntimeException::forUnsupportedType($listener);
        }
    }

    /**
     * Return configured listener providers.
     *
     * @return list<ListenerProviderInterface|string>
     */
    public function listenerProviders(): array
    {
        return $this->listenerProviders;
    }

    /**
     * Return configured event subscribers.
     *
     * @return list<EventSubscriberInterface|string>
     */
    public function eventSubscribers(): array
    {
        return $this->eventSubscribers;
    }

    /**
     * Return configured reflection-based listeners.
     *
     * @return list<ConfiguredReflectionListener>
     */
    public function reflectionListeners(): array
    {
        return $this->reflectionListeners;
    }

    /**
     * Return configured prioritized listeners.
     *
     * @return list<ConfiguredPrioritizedListener>
     */
    public function prioritizedListeners(): array
    {
        return $this->prioritizedListeners;
    }

    /**
     * Resolve listener attributes declared through {@see AsEventListener}.
     *
     * @param mixed $listener listener value to inspect
     *
     * @return list<AsEventListener> attribute instances resolved from the listener
     *
     * @throws RuntimeException thrown when an attribute target cannot resolve its event type
     * @throws ReflectionException thrown when reflective lookup fails
     */
    private function getReflectionAttributes(mixed $listener): array
    {
        if (! \is_object($listener) && (! \is_string($listener) || ! class_exists($listener))) {
            return [];
        }

        $reflection = new ReflectionClass($listener);
        $attributes = [];

        foreach ($reflection->getAttributes(AsEventListener::class) as $attribute) {
            $instance = $attribute->newInstance();
            $instance->method ??= '__invoke';
            $instance->event ??= $this->getReflectionEventType($reflection->getMethod($instance->method));
            $attributes[] = $instance;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
                $instance = $attribute->newInstance();
                $instance->method ??= $method->getName();
                $instance->event ??= $this->getReflectionEventType($method);
                $attributes[] = $instance;
            }
        }

        return $attributes;
    }

    /**
     * Resolve the event type handled by an attributed listener method.
     *
     * @param ReflectionMethod $method reflected listener method
     *
     * @return string event type declared by the first method parameter
     *
     * @throws RuntimeException thrown when the method does not expose a typed first parameter
     */
    private function getReflectionEventType(ReflectionMethod $method): string
    {
        $parameters = $method->getParameters();
        if ([] === $parameters) {
            throw RuntimeException::forMethodWithoutParameters();
        }

        $parameter = $parameters[0];

        if (! $parameter->hasType()) {
            throw RuntimeException::forMethodParameterWithoutType();
        }

        return $parameter->getType()
            ->getName();
    }

    /**
     * Resolve the event type handled by a callable listener.
     *
     * @param mixed $listener listener value to inspect
     *
     * @return string event type declared by the first callable parameter
     *
     * @throws RuntimeException thrown when the callable does not expose a typed first parameter
     * @throws ReflectionException thrown when reflective lookup fails
     */
    private function getCallableEventType(mixed $listener): string
    {
        $parameters = $this->getCallableReflector($listener)
            ->getParameters();

        if ([] === $parameters) {
            throw RuntimeException::forListenerWithoutParameters();
        }

        $parameter = $parameters[0];

        if (! $parameter->hasType()) {
            throw RuntimeException::forListenerParameterWithoutType();
        }

        return $parameter->getType()
            ->getName();
    }

    /**
     * Create a reflector for the provided callable listener value.
     *
     * @param mixed $listener listener value to reflect
     *
     * @return ReflectionFunctionAbstract reflection object for the callable
     *
     * @throws RuntimeException thrown when the listener cannot be reflected as a callable
     * @throws ReflectionException thrown when reflective lookup fails
     */
    private function getCallableReflector(mixed $listener): ReflectionFunctionAbstract
    {
        if ($listener instanceof Closure) {
            return new ReflectionFunction($listener);
        }

        if (\is_string($listener) && str_contains($listener, '::')) {
            [$class, $method] = explode('::', $listener, 2);

            return new ReflectionMethod($class, $method);
        }

        if (\is_array($listener) && isset($listener[0], $listener[1])) {
            return new ReflectionMethod($listener[0], $listener[1]);
        }

        if (\is_object($listener)) {
            return new ReflectionMethod($listener, '__invoke');
        }

        if (\is_string($listener) && \function_exists($listener)) {
            return new ReflectionFunction($listener);
        }

        if (\is_string($listener) && class_exists($listener)) {
            return new ReflectionMethod($listener, '__invoke');
        }

        throw RuntimeException::forUnsupportedType($listener);
    }
}
