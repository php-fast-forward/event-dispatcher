Subscribers
===========

The package includes ``EventSubscriberListenerProvider``, which adapts Symfony-style subscribers to the PSR-14
world.

Why Subscribers Are Useful
--------------------------

Subscribers work well when:

- one class should react to several events;
- you want listener priority close to the subscriber definition;
- you are migrating from Symfony conventions;
- you prefer method names over multiple small invokable listener classes.

Direct Provider Usage
---------------------

.. code-block:: php

   use FastForward\EventDispatcher\EventDispatcher;
   use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
   use Symfony\Component\EventDispatcher\EventSubscriberInterface;

   final readonly class SubscriptionActivated
   {
       public function __construct(
           public string $customerEmail,
           public string $plan,
       ) {}
   }

   final class SubscriptionLifecycleSubscriber implements EventSubscriberInterface
   {
       public static function getSubscribedEvents(): array
       {
           return [
               SubscriptionActivated::class => [
                   ['provisionAccess', 20],
                   ['sendReceipt', 10],
               ],
           ];
       }

       public function provisionAccess(SubscriptionActivated $event): void
       {
           echo 'Provisioning ' . $event->plan . PHP_EOL;
       }

       public function sendReceipt(SubscriptionActivated $event): void
       {
           echo 'Sending receipt to ' . $event->customerEmail . PHP_EOL;
       }
   }

   $provider = new EventSubscriberListenerProvider(
       new SubscriptionLifecycleSubscriber(),
   );

   $dispatcher = new EventDispatcher($provider);
   $dispatcher->dispatch(new SubscriptionActivated('team@example.com', 'pro'));

Supported ``getSubscribedEvents()`` Shapes
------------------------------------------

The provider supports the common Symfony formats:

.. list-table::
   :header-rows: 1

   * - Shape
     - Example
     - Meaning
   * - single method string
     - ``UserRegistered::class => 'onUserRegistered'``
     - call one method with default priority ``0``
   * - method and priority
     - ``UserRegistered::class => ['onUserRegistered', 20]``
     - call one method with explicit priority
   * - many methods for one event
     - ``UserRegistered::class => [['sendMail', 20], ['audit', 10]]``
     - call several methods in descending priority order

Subscribers by Event Class or String Name
-----------------------------------------

The event map key may be:

- a class name such as ``UserRegistered::class``;
- an arbitrary string such as ``billing.payment_received``.

When a subscriber handles a named event, the provider unwraps ``NamedEvent`` and passes the original event
object to the subscriber method.

Priority Rules
--------------

Higher priority values run first.

If two subscribers listen to the same event, the provider yields them according to the priority values stored in
its internal priority queue.

Class Strings and Instances
---------------------------

You may construct the provider with:

- subscriber instances;
- subscriber class names.

If you provide a class string that does not implement ``Symfony\Component\EventDispatcher\EventSubscriberInterface``,
the provider throws ``FastForward\EventDispatcher\Exception\InvalidArgumentException``.

When To Prefer Subscribers
--------------------------

Subscribers are often the best fit when a single bounded context owns several reactions to related events. If
each reaction is independent and you want very small classes, invokable listeners or attribute listeners may be
clearer.
