EventDispatcherServiceProvider
==============================

Namespace
---------

``FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider``

Purpose
-------

This service provider integrates the package with the Fast Forward container ecosystem. It registers the
dispatcher, provider aliases, and a set of extensions that classify listeners from configuration.

Registered Factories
--------------------

.. list-table::
   :header-rows: 1

   * - Service ID
     - Factory result
   * - ``Psr\EventDispatcher\ListenerProviderInterface``
     - alias to ``Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate``
   * - ``Psr\EventDispatcher\EventDispatcherInterface``
     - alias to ``FastForward\EventDispatcher\EventDispatcher``
   * - ``Symfony\Contracts\EventDispatcher\EventDispatcherInterface``
     - alias to ``FastForward\EventDispatcher\EventDispatcher``
   * - ``Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate``
     - aggregate over the built-in provider strategies
   * - ``FastForward\EventDispatcher\EventDispatcher``
     - concrete dispatcher
   * - ``Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider``
     - prioritized provider
   * - ``Phly\EventDispatcher\ListenerProvider\ReflectionBasedListenerProvider``
     - reflection-based provider
   * - ``FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider``
     - subscriber adapter
   * - ``FastForward\EventDispatcher\ServiceProvider\Configuration\ConfiguredListenerProviderCollection``
     - classified configuration snapshot

Registered Extensions
---------------------

The service provider also extends runtime services after construction.

.. list-table::
   :header-rows: 1

   * - Target service
     - Extension responsibility
   * - ``ListenerProviderAggregate``
     - attach configured custom listener providers
   * - ``PrioritizedListenerProvider``
     - register attributed listeners
   * - ``ReflectionBasedListenerProvider``
     - register generic callables and invokable listeners
   * - ``EventSubscriberListenerProvider``
     - register Symfony subscribers

Configuration Entry Point
-------------------------

The main configuration key is:

.. code-block:: php

   Psr\EventDispatcher\ListenerProviderInterface::class

Its value should be a list of listener declarations. The package then classifies each entry into the correct
provider strategy.

Default Resolution Order
------------------------

In the current container composition, listeners are resolved in this order:

1. prioritized attribute listeners;
2. reflection-based listeners;
3. Symfony subscribers;
4. extra custom listener providers attached to the aggregate.

This order is important when several listener styles target the same event.

When To Use This Service Provider
---------------------------------

Use it when you want:

- declarative bootstrap;
- lazy resolution of listener classes from the container;
- mixed listener styles in one configuration array;
- Symfony subscriber and attribute support without manual plumbing.
