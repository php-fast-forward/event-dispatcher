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

final class EventDispatcherContainer implements ContainerInterface
{
    private ServiceProviderInterface $serviceProvider;

    private ContainerInterface $wrapperContainer;

    private array $resolved;

    public function __construct(
        array $listeners = [],
        ?ContainerInterface $wrapperContainer = null,
    ) {
        $this->wrapperContainer = $wrapperContainer ?? $this;

        if (empty($listeners) && $wrapperContainer->has('config.listeners')) {
            $listeners = $wrapperContainer->get('config.listeners');
        }

        $this->serviceProvider = new EventDispatcherServiceProvider($this, ...$listeners);
    }

    public function has(string $id): bool
    {
        return isset($this->resolved[$id]) || \array_key_exists($id, $this->serviceProvider->getFactories());
    }

    public function get(string $id): mixed
    {
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        $factory = $this->serviceProvider->getFactories();

        if (!\array_key_exists($id, $factory)) {
            throw NotFoundException::forServiceID($id);
        }

        try {
            $service    = $factory($this->wrapperContainer);
            $extensions = $this->serviceProvider->getExtensions();

            if (\array_key_exists($id, $extensions)) {
                $extensions[$id]($this->wrapperContainer, $service);
            }
        } catch (ContainerExceptionInterface $containerException) {
            throw ContainerException::forInvalidService($id, $containerException);
        }

        return $this->resolved[$id] = $service;
    }
}
