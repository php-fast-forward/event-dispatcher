Quickstart
==========

This quickstart shows the most beginner-friendly path: use the Fast Forward container, register one listener,
and dispatch one event.

Minimal Example
---------------

.. code-block:: php

   <?php

   declare(strict_types=1);

   use FastForward\Config\ArrayConfig;
   use FastForward\Container\ContainerInterface;
   use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
   use Psr\EventDispatcher\EventDispatcherInterface;
   use Psr\EventDispatcher\ListenerProviderInterface;

   use function FastForward\Container\container;

   require_once __DIR__ . '/../vendor/autoload.php';

   final readonly class UserRegistered
   {
       public function __construct(public string $email) {}
   }

   final class SendWelcomeEmailListener
   {
       public function __invoke(UserRegistered $event): void
       {
           echo 'Sending welcome email to ' . $event->email . PHP_EOL;
       }
   }

   $config = new ArrayConfig([
       ContainerInterface::class => [
           EventDispatcherServiceProvider::class,
       ],
       ListenerProviderInterface::class => [
           SendWelcomeEmailListener::class,
       ],
   ]);

   $container = container($config);
   $dispatcher = $container->get(EventDispatcherInterface::class);

   $dispatcher->dispatch(new UserRegistered('github@mentordosnerds.com'));

Output
------

.. code-block:: text

   Sending welcome email to github@mentordosnerds.com


What Happened
-------------

1. ``EventDispatcherServiceProvider`` registered the package services in the container.
2. The configuration entry under ``ListenerProviderInterface::class`` told the package which listeners exist.
3. The listener class was classified as a reflection-based listener because it is an invokable class with a
   typed first argument.
4. Dispatching ``UserRegistered`` caused the listener to be called with that object.

Where To Go Next
----------------

- If you want string-based names such as ``billing.payment_received``, read :doc:`../usage/named-events`.
- If you prefer Symfony subscribers, read :doc:`../usage/subscribers`.
- If you want one provider to observe every dispatched object, read :doc:`../usage/wildcard-listeners`.
- If you want to understand every supported registration style, read :doc:`../advanced/configuration-model`.
