Events
======

This package ships several event-related building blocks in addition to your own domain event classes.

Overview
--------

.. list-table::
   :header-rows: 1

   * - Type
     - Role
     - Key methods
   * - ``NamedEvent``
     - wraps another event with an explicit name
     - ``getName()``, ``getEvent()``, ``isPropagationStopped()``
   * - ``ErrorEvent``
     - represents a listener failure
     - ``getEvent()``, ``getListener()``, ``getThrowable()``, ``stopPropagation()``
   * - ``Event``
     - generic Symfony-compatible base event
     - ``isPropagationStopped()``, ``stopPropagation()``
   * - ``StoppableEventTrait``
     - reusable propagation state storage
     - ``isPropagationStopped()``, ``stopPropagation()``

NamedEvent
----------

``NamedEvent`` is useful when you want to dispatch a regular event object under an explicit string name.

Key properties:

- it stores the original event object;
- it stores the effective dispatch name;
- if the wrapped event is stoppable, it mirrors that stoppable state.

The default name is the wrapped event class name when no explicit name is provided.

ErrorEvent
----------

``ErrorEvent`` extends ``Exception`` and implements ``StoppableEventInterface``.

It stores:

- the original event being processed when the failure happened;
- the listener that failed;
- the original throwable, accessible through ``getThrowable()``.

Because it extends ``Exception``, it can move through tooling that already understands exceptions while still
being dispatched like an event.

Event and StoppableEventTrait
-----------------------------

Use these when your event should be able to halt propagation.

``Event`` is the best starting point when a simple base class is enough and you want compatibility with
``Symfony\Contracts\EventDispatcher\Event``.

``StoppableEventTrait`` is useful when your event already extends another class and cannot inherit from
``Event``.

Design Note
-----------

``Event`` intentionally exposes Symfony's public ``stopPropagation()`` API for interoperability.

``StoppableEventTrait`` exposes the same public API, which makes it practical for listeners to stop
propagation directly even when your event cannot extend ``Event``.
