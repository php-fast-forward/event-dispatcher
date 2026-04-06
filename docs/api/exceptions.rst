Exceptions
==========

The public exception types in this package describe configuration and runtime mistakes around listener
classification and subscriber registration.

InvalidArgumentException
------------------------

Namespace:

``FastForward\EventDispatcher\Exception\InvalidArgumentException``

Named factories currently include:

- ``forExpectedArrayList(array $array)``
- ``forInvalidEventSubscriber(string $eventSubscriber, string $expectedInterface)``

The most relevant public scenario today is an invalid subscriber class string passed to
``EventSubscriberListenerProvider``.

RuntimeException
----------------

Namespace:

``FastForward\EventDispatcher\Exception\RuntimeException``

Named factories currently include:

- ``forUnsupportedType(mixed $listener)``
- ``forMethodWithoutParameters()``
- ``forMethodParameterWithoutType()``
- ``forListenerWithoutParameters()``
- ``forListenerParameterWithoutType()``

These exceptions are typically raised during automatic listener classification when the package cannot determine
how a configured listener should be invoked.

Failure Conditions To Watch For
-------------------------------

.. list-table::
   :header-rows: 1

   * - Condition
     - Typical cause
   * - unsupported listener type
     - value in configuration is neither a provider, subscriber, nor a callable listener shape
   * - method has no parameters
     - attributed listener method does not accept an event argument
   * - parameter has no type
     - attributed listener method omits the event type and does not set an explicit attribute event
   * - listener has no parameters
     - reflection-based callable does not accept an event argument
   * - listener parameter has no type
     - reflection-based callable first argument is untyped

Troubleshooting Tip
-------------------

When one of these exceptions appears during container bootstrap, inspect the value you placed under
``Psr\EventDispatcher\ListenerProviderInterface::class`` first. In most projects, the root cause is located
there.
