Integration
===========

This package can sit comfortably in different architectural styles as long as you decide how listeners are
provided.

Pure PSR-14 Integration
-----------------------

If your application already has a listener provider, use the dispatcher directly.

.. code-block:: php

   use FastForward\EventDispatcher\EventDispatcher;
   use Psr\EventDispatcher\ListenerProviderInterface;

   $provider = new YourListenerProvider();
   $dispatcher = new EventDispatcher($provider);

This keeps the package focused on dispatch while your application owns listener lookup.

Fast Forward Container Integration
----------------------------------

If your application already uses ``fast-forward/container``, the service provider is usually the most productive
option.

Benefits:

- one configuration list for several listener styles;
- lazy listener resolution from the container;
- PSR and Symfony contract aliases out of the box;
- support for custom listener providers in the same pipeline.

Working With the Symfony Contracts Interface
--------------------------------------------

The concrete dispatcher implements ``Symfony\Contracts\EventDispatcher\EventDispatcherInterface`` so you can
write code that dispatches named events without coupling directly to the concrete class.

This is helpful when:

- you want ``dispatch($event, $name)`` syntax;
- your team already knows the Symfony contracts interface;
- you want to keep named dispatch explicit in type hints.

Migrating Existing Subscriber Code
----------------------------------

Many subscriber classes can be reused as-is because the package honors Symfony's standard
``getSubscribedEvents()`` formats.

Typical migration path:

1. keep your subscriber classes unchanged;
2. register them under ``ListenerProviderInterface::class`` in configuration;
3. inject either the PSR dispatcher interface or the Symfony contracts interface.

Combining With Logging
----------------------

If you want centralized failure visibility, wire ``LogErrorEventListener`` and a PSR-3 logger into the
container. The listener will observe ``ErrorEvent`` and record the original exception.

If you want to observe every dispatched object instead of only failures, register
``LogEventListenerProvider``. Because the dispatcher also emits a ``NamedEvent`` wrapper for regular dispatches,
the logger can record both the typed event and its named form.

Integration Boundary
--------------------

This package does not impose a framework lifecycle. It gives you dispatch, provider composition, and listener
classification. Application-level concerns such as retries, transport, persistence, or rollback remain your
responsibility.
