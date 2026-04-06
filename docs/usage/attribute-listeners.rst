Attribute Listeners
===================

This package supports Symfony ``AsEventListener`` attributes through its service-provider integration.

Important Limitation
--------------------

Automatic attribute discovery happens when you use ``EventDispatcherServiceProvider`` together with the Fast
Forward container configuration model.

If you instantiate ``EventDispatcher`` manually, this package does not scan classes for attributes by itself.

Method-Level Attribute Example
------------------------------

.. code-block:: php

   use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

   final readonly class OrderConfirmed
   {
       public function __construct(public string $orderNumber) {}
   }

   final class ReserveStockListener
   {
       #[AsEventListener(priority: 100)]
       public function __invoke(OrderConfirmed $event): void
       {
           echo 'Reserving stock for ' . $event->orderNumber . PHP_EOL;
       }
   }

   final class NotifyOperationsListener
   {
       #[AsEventListener(priority: 10)]
       public function __invoke(OrderConfirmed $event): void
       {
           echo 'Notifying operations for ' . $event->orderNumber . PHP_EOL;
       }
   }

Class-Level Attributes Are Also Supported
-----------------------------------------

The package inspects:

- class-level ``AsEventListener`` attributes;
- method-level ``AsEventListener`` attributes on public methods.

When the attribute does not explicitly declare an event name, the package infers the event type from the first
parameter of the target method.

That means these rules must hold:

- the method must have at least one parameter;
- the first parameter must have a type declaration.

Otherwise, a ``FastForward\EventDispatcher\Exception\RuntimeException`` is raised while classifying listeners.

How Registration Works
----------------------

With container integration, you place the listener class under the configuration key
``Psr\EventDispatcher\ListenerProviderInterface::class``.

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use FastForward\Container\ContainerInterface;
   use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
   use Psr\EventDispatcher\ListenerProviderInterface;

   $config = new ArrayConfig([
       ContainerInterface::class => [
           EventDispatcherServiceProvider::class,
       ],
       ListenerProviderInterface::class => [
           ReserveStockListener::class,
           NotifyOperationsListener::class,
       ],
   ]);

Runtime Behavior
----------------

Attribute listeners are registered in the prioritized listener provider.

In the default service-provider composition, prioritized listeners are consulted before reflection-based
listeners, subscriber-based listeners, and extra custom providers attached through configuration.

Best Practices
--------------

- Use attributes when you want the event binding to live next to the listener method.
- Keep the first parameter clearly typed.
- Prefer explicit method names for multi-method listener classes.
- Do not rely on attributes unless the service provider is part of your application bootstrap.
