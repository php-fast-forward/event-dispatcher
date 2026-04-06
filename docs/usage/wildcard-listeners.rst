Wildcard Listeners
==================

``WildcardListenerProvider`` is a small base class for custom listener providers that should receive every
dispatched object.

Why This Exists
---------------

Most listener registration in this package is type-driven. That is ideal for domain listeners, but some
cross-cutting concerns should observe everything:

- logging;
- metrics;
- audit trails;
- tracing;
- diagnostics.

For those scenarios, a wildcard provider is often simpler than registering many separate typed listeners.

Base Contract
-------------

Subclasses only implement ``__invoke(object $event): void``. The base class already satisfies
``ListenerProviderInterface`` by yielding itself for every dispatched object.

.. code-block:: php

   use FastForward\EventDispatcher\ListenerProvider\WildcardListenerProvider;

   abstract class AuditListenerProvider extends WildcardListenerProvider
   {
       abstract public function __invoke(object $event): void;
   }

Logging Every Dispatched Object
-------------------------------

The package ships ``LogEventListenerProvider`` as a ready-to-use wildcard provider.

.. code-block:: php

   <?php

   declare(strict_types=1);

   use FastForward\Config\ArrayConfig;
   use FastForward\Container\ContainerInterface;
   use FastForward\Container\ServiceProvider\ArrayServiceProvider;
   use FastForward\EventDispatcher\ListenerProvider\LogEventListenerProvider;
   use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
   use Psr\EventDispatcher\ListenerProviderInterface;
   use Psr\Log\AbstractLogger;
   use Psr\Log\LoggerInterface;
   use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

   use function FastForward\Container\container;

   final readonly class UserRegistered
   {
       public function __construct(public string $email) {}
   }

   final class StdoutLogger extends AbstractLogger
   {
       public function log($level, \Stringable|string $message, array $context = []): void
       {
           echo '[' . strtoupper((string) $level) . '] ' . $message . PHP_EOL;
           echo 'Event class: ' . $context['event_class'] . PHP_EOL;

           if (isset($context['event_name'])) {
               echo 'Event name: ' . $context['event_name'] . PHP_EOL;
           }

           if (isset($context['wrapped_event_class'])) {
               echo 'Wrapped event class: ' . $context['wrapped_event_class'] . PHP_EOL;
           }

           echo PHP_EOL;
       }
   }

   $config = new ArrayConfig([
       ContainerInterface::class => [
           EventDispatcherServiceProvider::class,
       ],
       ListenerProviderInterface::class => [
           LogEventListenerProvider::class,
       ],
   ]);

   $container = container(
       $config,
       new ArrayServiceProvider([
           LoggerInterface::class => static fn(): LoggerInterface => new StdoutLogger(),
       ]),
   );

   $dispatcher = $container->get(EventDispatcherInterface::class);

   $dispatcher->dispatch(new UserRegistered('github@mentordosnerds.com'), 'users.registered');

Output
------

.. code-block:: text

   [INFO] Event dispatched
   Event class: UserRegistered

   [INFO] Event dispatched
   Event class: FastForward\EventDispatcher\Event\NamedEvent
   Event name: users.registered
   Wrapped event class: UserRegistered

What Gets Observed
------------------

``LogEventListenerProvider`` receives:

- the original dispatched event object;
- the generated ``NamedEvent`` wrapper for regular dispatches;
- ``ErrorEvent`` objects emitted when a listener throws.

That makes it suitable for system-wide observation without coupling the logger to one event class.

Registration Notes
------------------

When you register a wildcard provider through ``EventDispatcherServiceProvider``, it is treated as a custom
``ListenerProviderInterface`` implementation and attached to the aggregate pipeline.

.. note::

   Wildcard providers observe objects, not string patterns. If you need string-based event-name patterns such
   as ``billing.*``, that matching logic still belongs in your own custom provider implementation.
