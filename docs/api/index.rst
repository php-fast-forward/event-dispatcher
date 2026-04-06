API Reference
=============

This section documents the main public concepts exposed by the package.

.. list-table::
   :header-rows: 1

   * - Component
     - Purpose
   * - ``EventDispatcher``
     - dispatches events through a PSR-14 listener provider and optional named wrappers
   * - ``NamedEvent``
     - wraps an event object with an explicit string name
   * - ``ErrorEvent``
     - represents a listener failure as a dispatchable event
   * - ``Event`` and ``StoppableEventTrait``
     - base building blocks for stoppable event implementations
   * - ``WildcardListenerProvider``
     - base class for listener providers that should observe every dispatched object
   * - ``LogEventListenerProvider``
     - wildcard provider that logs dispatched objects through PSR-3
   * - ``EventSubscriberListenerProvider``
     - adapts Symfony subscribers to PSR-14 listener resolution
   * - ``EventDispatcherServiceProvider``
     - wires the package into the Fast Forward container

.. toctree::
   :maxdepth: 1

   event-dispatcher
   events
   listener-providers
   service-provider
   exceptions
