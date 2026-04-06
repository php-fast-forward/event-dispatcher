Named Events
============

Named events let you keep rich event objects while also addressing them with explicit string identifiers.

When To Use Named Events
------------------------

Named events are useful when:

- you want stable external names such as ``billing.payment_received``;
- you are migrating from a string-based event system;
- multiple classes should conceptually map to the same integration event name;
- you want class-based listeners and name-based listeners to react to the same dispatch.

How To Dispatch a Named Event
-----------------------------

Use the Symfony contracts interface or the concrete dispatcher so you can pass the optional ``$eventName``.

.. code-block:: php

   use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

   final readonly class PaymentReceived
   {
       public function __construct(
           public string $invoiceId,
           public float $amount,
       ) {}
   }

   $dispatcher->dispatch(
       new PaymentReceived('INV-2026-0001', 199.90),
       'billing.payment_received',
   );

What Happens Internally
-----------------------

For a named dispatch, the package first dispatches the original ``PaymentReceived`` object. After that, it
dispatches ``NamedEvent($originalEvent, 'billing.payment_received')``.

This means you can combine:

- listeners keyed by ``PaymentReceived::class``;
- listeners keyed by ``billing.payment_received``.

Subscriber Example
------------------

.. code-block:: php

   use Symfony\Component\EventDispatcher\EventSubscriberInterface;

   final class BillingSubscriber implements EventSubscriberInterface
   {
       public static function getSubscribedEvents(): array
       {
           return [
               'billing.payment_received' => 'onPaymentReceived',
           ];
       }

       public function onPaymentReceived(PaymentReceived $event): void
       {
           echo $event->invoiceId . PHP_EOL;
       }
   }

The subscriber receives the original ``PaymentReceived`` object, not the ``NamedEvent`` wrapper.

NamedEvent API
--------------

``FastForward\EventDispatcher\Event\NamedEvent`` exposes two pieces of information:

- ``getName()``: the effective string name
- ``getEvent()``: the wrapped original object

It also mirrors stoppable state from the wrapped event when that event implements
``Psr\EventDispatcher\StoppableEventInterface``.

Gotchas
-------

.. warning::

   The dispatcher does not deduplicate listeners across the typed pass and the named pass. If the same
   callable is effectively registered in both channels, it can run twice.

.. note::

   If you dispatch a ``NamedEvent`` instance directly, the dispatcher returns the wrapped original event
   instead of the wrapper.

Recommended Use
---------------

Use named events when the name itself carries business meaning outside the PHP class name. If the class name is
already your canonical identifier, regular typed dispatch is usually simpler.
