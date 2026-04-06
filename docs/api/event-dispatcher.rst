EventDispatcher
===============

Namespace
---------

``FastForward\EventDispatcher\EventDispatcher``

Purpose
-------

``EventDispatcher`` is the central runtime class of the package. It accepts any
``Psr\EventDispatcher\ListenerProviderInterface`` and dispatches event objects through it.

Implemented Contracts
---------------------

- ``Psr\EventDispatcher\EventDispatcherInterface``
- ``Symfony\Contracts\EventDispatcher\EventDispatcherInterface``

Constructor
-----------

.. code-block:: php

   public function __construct(
       ListenerProviderInterface $listenerProvider
   )

Dispatch Signature
------------------

.. code-block:: php

   public function dispatch(object $event, ?string $eventName = null): object

Behavior Summary
----------------

The method:

1. resolves listeners for the original event object;
2. calls them in order;
3. respects ``StoppableEventInterface`` before and during dispatch;
4. if the event is not already a ``NamedEvent``, dispatches a named wrapper next;
5. returns the original event object.

Error Semantics
---------------

If a listener throws:

- the dispatcher emits ``ErrorEvent``;
- error listeners may observe the failure;
- the original throwable is rethrown.

If the current event is already an ``ErrorEvent`` and an error listener throws again, the dispatcher rethrows the
original throwable stored inside the ``ErrorEvent`` to prevent recursive error dispatch.

Named Dispatch Semantics
------------------------

If you pass ``$eventName``:

- the original event is dispatched first;
- a ``NamedEvent`` wrapper with that name is dispatched second.

If you pass a ``NamedEvent`` directly, the dispatcher does not wrap it again. Instead, after processing the
wrapper listeners, it returns the original wrapped event.

Practical Implications
----------------------

- The same event can be observed by class-based and string-based listeners.
- Registering the same callable in both passes can lead to two executions.
- Consumers typed against the PSR interface will not normally expose the optional ``$eventName`` argument in
  their type hints, even though the concrete class supports it.
