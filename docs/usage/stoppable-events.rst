Stoppable Events
================

If an event should be able to halt propagation, implement ``Psr\EventDispatcher\StoppableEventInterface``.

Convenience Helpers
-------------------

The package provides:

- ``FastForward\EventDispatcher\Event\Event``
- ``FastForward\EventDispatcher\Event\StoppableEventTrait``

Both store propagation state for you.

The Important Detail
--------------------

``Event`` extends Symfony's base event class and keeps its public ``stopPropagation()`` API.

``StoppableEventTrait`` exposes the same public method, which means listeners can call it directly too.

Recommended Pattern
-------------------

.. code-block:: php

   use FastForward\EventDispatcher\Event\Event;

   final class UserDeletionRequested extends Event
   {
       public function __construct(public readonly int $userId) {}
   }

   final class PreventAdminDeletionListener
   {
       public function __invoke(UserDeletionRequested $event): void
       {
           if ($event->userId === 1) {
               $event->stopPropagation();
           }
       }
   }

How the Dispatcher Treats Stoppable Events
------------------------------------------

- If the event is already stopped before dispatch starts, the dispatcher returns immediately.
- After each listener call, the dispatcher checks whether propagation has been stopped.
- If the event is later wrapped in ``NamedEvent``, the wrapper mirrors the stoppable state of the original
  event.

What This Is Good For
---------------------

Stoppable events are a good fit for:

- validation pipelines;
- veto rules;
- security checks;
- first-match workflows where later listeners should not run.

What It Does Not Do
-------------------

Stopping propagation does not undo work already done by earlier listeners. It only prevents later listeners from
running.
