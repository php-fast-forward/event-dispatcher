Configuration Model
===================

When you use ``EventDispatcherServiceProvider``, the package reads one list from configuration and classifies
every entry.

Main Configuration Key
----------------------

.. code-block:: php

   use Psr\EventDispatcher\ListenerProviderInterface;

   ListenerProviderInterface::class => [
       // listeners go here
   ]

What You Can Put In the List
----------------------------

.. list-table::
   :header-rows: 1

   * - Declaration shape
     - Classified as
     - Notes
   * - ``SomeProvider::class`` implementing ``ListenerProviderInterface``
     - custom provider
     - resolved from the container when available; ideal for wildcard observers such as logging or metrics
   * - ``SomeSubscriber::class`` implementing ``EventSubscriberInterface``
     - subscriber
     - supports Symfony subscriber maps and priorities
   * - ``SomeAttributedListener::class`` with ``AsEventListener``
     - prioritized listener
     - attributes are inspected before other categories
   * - invokable object
     - reflection-based listener
     - first argument type determines the event type
   * - closure
     - reflection-based listener
     - first argument type is required
   * - global function name
     - reflection-based listener
     - function must exist
   * - ``ClassName::method`` string
     - reflection-based listener
     - method signature must expose a typed first parameter
   * - callable array
     - reflection-based listener
     - example: ``[$listener, 'onEvent']``

Mixed Example
-------------

.. code-block:: php

   use Psr\EventDispatcher\ListenerProviderInterface;

   ListenerProviderInterface::class => [
       ReserveStockListener::class,            // AsEventListener => prioritized
       SendWelcomeEmailListener::class,        // invokable class => reflection-based
       BillingSubscriber::class,               // EventSubscriberInterface => subscriber
       AuditTrailProvider::class,              // ListenerProviderInterface => custom provider
       static function (UserRegistered $event): void {
           // closure listener
       },
       App\Listeners\MetricsListener::class . '::onUserRegistered',
   ]

Inference Rules
---------------

For attributed listeners:

- if the attribute already defines the event explicitly, that value is used;
- otherwise, the package infers the event from the first parameter type of the target method.

For reflection-based listeners:

- the first parameter must exist;
- the first parameter must be typed.

If either rule is violated, listener classification fails with ``RuntimeException``.

Container Resolution Rules
--------------------------

When a configuration entry is a string and the container has a service for that string, the package resolves it
lazily through the container.

This matters especially for:

- invokable listener classes;
- attributed listener classes;
- subscriber classes;
- custom provider classes.

Subtle but Important Rule
-------------------------

Attribute detection happens before subscriber or provider detection. If a class is both attributed and otherwise
looks like another supported type, the attribute path wins.
