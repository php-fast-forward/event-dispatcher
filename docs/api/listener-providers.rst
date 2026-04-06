Listener Providers
==================

The package works with any PSR-14 listener provider, and it also ships dedicated helpers for subscribers and
wildcard observers.

WildcardListenerProvider
------------------------

Namespace:

``FastForward\EventDispatcher\ListenerProvider\WildcardListenerProvider``

Purpose:

Provide a small base class for listener providers that should receive every dispatched object.

Contract:

.. code-block:: php

   abstract public function __invoke(object $event): void

Key behaviors:

- yields itself for every object passed to ``getListenersForEvent()``;
- works well for cross-cutting observers such as logging, metrics, and auditing;
- sees both the original event object and the generated ``NamedEvent`` wrapper.

LogEventListenerProvider
------------------------

Namespace:

``FastForward\EventDispatcher\ListenerProvider\LogEventListenerProvider``

Purpose:

Log every dispatched object through a PSR-3 logger.

Constructor:

.. code-block:: php

   public function __construct(LoggerInterface $logger, string $level = LogLevel::INFO)

Key behaviors:

- logs the current object under the ``event`` and ``event_class`` context keys;
- adds ``event_name``, ``wrapped_event``, and ``wrapped_event_class`` when the event is a ``NamedEvent``;
- adds ``exception``, ``original_event``, and ``original_event_class`` when the event is an ``ErrorEvent``.

EventSubscriberListenerProvider
-------------------------------

Namespace:

``FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider``

Purpose:

Adapt Symfony ``EventSubscriberInterface`` subscribers to ``Psr\EventDispatcher\ListenerProviderInterface``.

Constructor:

.. code-block:: php

   public function __construct(EventSubscriberInterface|string ...$eventSubscribers)

Key behaviors:

- subscribers are indexed by event name or event class;
- listeners are yielded in descending priority order;
- ``NamedEvent`` instances are unwrapped before subscriber methods are invoked;
- invalid subscriber class strings raise ``InvalidArgumentException``.

Supported Subscriber Value Shapes
---------------------------------

- subscriber instances;
- subscriber class names.

Service Provider Composition
----------------------------

When you use ``EventDispatcherServiceProvider``, the final listener pipeline is composed from several provider
strategies.

.. list-table::
   :header-rows: 1

   * - Strategy
     - Runtime class
     - Typical source in configuration
   * - prioritized listeners
     - ``Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider``
     - listeners with ``AsEventListener`` attributes
   * - reflection-based listeners
     - ``Phly\EventDispatcher\ListenerProvider\ReflectionBasedListenerProvider``
     - invokable classes, closures, functions, callables
   * - subscriber listeners
     - ``FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider``
     - Symfony subscribers
   * - attached custom providers
     - any ``ListenerProviderInterface``
     - your own provider implementations, including wildcard providers

Supported Configured Listener Shapes
------------------------------------

With service-provider integration, the configuration classifier can recognize:

- ``ListenerProviderInterface`` implementations or service IDs;
- ``EventSubscriberInterface`` implementations or service IDs;
- listener classes or objects marked with ``AsEventListener``;
- invokable objects;
- invokable class strings resolvable by the container;
- closures;
- global function names;
- ``ClassName::method`` strings;
- callable arrays such as ``[$listener, 'method']``.

Classification Rules
--------------------

The classifier checks for ``AsEventListener`` attributes first. That means an attributed class is treated as a
prioritized listener even if it could also fit another category.
