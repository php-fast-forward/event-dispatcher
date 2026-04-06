Dispatching Events
==================

At its core, this package dispatches plain PHP objects.

Basic Typed Dispatch
--------------------

The standard PSR-14 flow is simple: create an event object and dispatch it.

.. code-block:: php

   use Psr\EventDispatcher\EventDispatcherInterface;

   final readonly class PasswordResetRequested
   {
       public function __construct(public string $email) {}
   }

   $dispatcher->dispatch(new PasswordResetRequested('alice@example.com'));

How Listener Resolution Works
-----------------------------

When you dispatch an event object, the dispatcher:

1. asks the listener provider for listeners that match the original object;
2. calls those listeners in provider order;
3. stops early if the event implements ``StoppableEventInterface`` and propagation is stopped;
4. if the event is not already a ``NamedEvent``, wraps it in ``NamedEvent`` and dispatches that wrapper too.

That last step is important. It means one dispatch can trigger:

- object-based listeners keyed by the event class; and
- string-based listeners keyed by an explicit event name.

Return Value
------------

The dispatcher always gives you the original event object back.

This remains true even when a ``NamedEvent`` wrapper is used internally. If you dispatch a ``NamedEvent``
yourself, the dispatcher returns the wrapped original event.

Why This Matters
----------------

Returning the original event makes it easy to:

- inspect state changes applied by listeners;
- read flags from stoppable events;
- keep a consistent API whether you use plain or named dispatch.

Example With Post-Dispatch Inspection
-------------------------------------

.. code-block:: php

   final class NewsletterOptInRecorded
   {
       public bool $mailScheduled = false;

       public function __construct(public string $email) {}
   }

   final class ScheduleMailListener
   {
       public function __invoke(NewsletterOptInRecorded $event): void
       {
           $event->mailScheduled = true;
       }
   }

   $event = new NewsletterOptInRecorded('team@example.com');
   $event = $dispatcher->dispatch($event);

   var_dump($event->mailScheduled); // true

Common Beginner Mistakes
------------------------

- Registering a listener without a typed first parameter when using automatic listener classification.
- Expecting named event support while depending only on the PSR interface.
- Forgetting that named dispatch performs an additional wrapper dispatch after the typed dispatch.
