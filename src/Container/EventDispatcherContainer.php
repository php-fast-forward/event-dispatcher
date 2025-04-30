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

namespace FastForward\EventDispatcher\Container;

use FastForward\EventDispatcher\Exception\ContainerException;
use FastForward\EventDispatcher\Exception\NotFoundException;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Class EventDispatcherContainer.
 *
 * A PSR-11 compliant container implementation specifically designed to provide services
 * related to event dispatching. It acts as a factory and registry for event listeners
 * and dispatcher-related services, using a service provider pattern.
 *
 * This container SHALL be used where event-driven configuration is required and MAY
 * optionally wrap another container for cascading service resolution.
 *
 * @package FastForward\EventDispatcher\Container
 */
final class EventDispatcherContainer implements ContainerInterface
{
    /**
     * @const string The standard identifier for retrieving the listeners list.
     */
    public const ALIAS = 'config.listeners';

    /**
     * @var ServiceProviderInterface provides factories and extensions for service construction
     */
    private ServiceProviderInterface $serviceProvider;

    /**
     * @var ContainerInterface the underlying container used for delegation, if available
     */
    private ContainerInterface $wrapperContainer;

    /**
     * @var array<string, mixed> cached resolved services by their identifiers
     */
    private array $cache;

    /**
     * Constructs a new EventDispatcherContainer instance.
     *
     * If no listeners are passed and a wrapper container is available, it will attempt
     * to retrieve the listeners from the 'config.listeners' service in the wrapper container.
     *
     * @param array                   $listeners        an array of event listener callables or definitions
     * @param null|ContainerInterface $wrapperContainer optional container for service delegation
     */
    public function __construct(
        array $listeners = [],
        ?ContainerInterface $wrapperContainer = null,
    ) {
        if (empty($listeners)
            && null !== $wrapperContainer
            && $wrapperContainer->has(self::ALIAS)
        ) {
            $listeners = $wrapperContainer->get(self::ALIAS);
        }

        $this->wrapperContainer = $wrapperContainer ?? $this;
        $this->serviceProvider  = new EventDispatcherServiceProvider($this, ...$listeners);
    }

    /**
     * Determines if the container can return an entry for the given identifier.
     *
     * @param string $id identifier of the entry to look for
     *
     * @return bool true if the entry exists, false otherwise
     */
    public function has(string $id): bool
    {
        return isset($this->cache[$id]) || \array_key_exists($id, $this->serviceProvider->getFactories());
    }

    /**
     * Retrieves a service from the container by its identifier.
     *
     * This method SHALL return a cached instance if available, otherwise it resolves
     * the service using the factory provided by the service provider.
     *
     * If the service has a corresponding extension, it SHALL be applied post-construction.
     *
     * @param string $id the identifier of the service to retrieve
     *
     * @return mixed the service instance associated with the identifier
     *
     * @throws NotFoundException  if no factory exists for the given identifier
     * @throws ContainerException if service construction fails due to container errors
     */
    public function get(string $id): mixed
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $factory = $this->serviceProvider->getFactories();

        if (!\array_key_exists($id, $factory) || !\is_callable($factory[$id])) {
            throw NotFoundException::forServiceID($id);
        }

        try {
            $service    = $factory[$id]($this->wrapperContainer);
            $extensions = $this->serviceProvider->getExtensions();

            if (\array_key_exists($id, $extensions) && \is_callable($extensions[$id])) {
                $extensions[$id]($this->wrapperContainer, $service);
            }
        } catch (ContainerExceptionInterface $containerException) {
            throw ContainerException::forInvalidService($id, $containerException);
        }

        return $this->cache[$id] = $service;
    }
}
