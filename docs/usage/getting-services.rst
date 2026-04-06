Getting Services
================

This package can be used either without a container or through the Fast Forward container integration.

Direct Construction
-------------------

The most direct way to use the package is to create a listener provider and pass it to the dispatcher.

With Symfony subscribers
^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

   use FastForward\EventDispatcher\EventDispatcher;
   use FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider;
   use Symfony\Component\EventDispatcher\EventSubscriberInterface;

   final readonly class UserRegistered
   {
       public function __construct(public string $email) {}
   }

   final class WelcomeSubscriber implements EventSubscriberInterface
   {
       public static function getSubscribedEvents(): array
       {
           return [
               UserRegistered::class => 'onUserRegistered',
           ];
       }

       public function onUserRegistered(UserRegistered $event): void
       {
           echo $event->email . PHP_EOL;
       }
   }

   $provider = new EventSubscriberListenerProvider(new WelcomeSubscriber());
   $dispatcher = new EventDispatcher($provider);

   $dispatcher->dispatch(new UserRegistered('new@example.com'));

With your own provider
^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

   use FastForward\EventDispatcher\EventDispatcher;
   use Psr\EventDispatcher\ListenerProviderInterface;

   final readonly class UserRegistered
   {
       public function __construct(public string $email) {}
   }

   final class SingleListenerProvider implements ListenerProviderInterface
   {
       public function getListenersForEvent(object $event): iterable
       {
           if (! $event instanceof UserRegistered) {
               return [];
           }

           yield static function (UserRegistered $event): void {
               echo 'Handled ' . $event->email . PHP_EOL;
           };
       }
   }

   $dispatcher = new EventDispatcher(new SingleListenerProvider());

If your provider should observe every dispatched object instead of filtering by one event type, extend
``WildcardListenerProvider`` and implement ``__invoke(object $event): void``.

Retrieving Services From the Container
--------------------------------------

When you register ``EventDispatcherServiceProvider``, the container exposes several useful services.

.. list-table::
   :header-rows: 1

   * - Service ID
     - Concrete value
     - When to request it
   * - ``Psr\EventDispatcher\EventDispatcherInterface``
     - ``FastForward\EventDispatcher\EventDispatcher``
     - The default choice for regular typed event dispatch
   * - ``Symfony\Contracts\EventDispatcher\EventDispatcherInterface``
     - ``FastForward\EventDispatcher\EventDispatcher``
     - Use this when you want to dispatch with an explicit event name
   * - ``Psr\EventDispatcher\ListenerProviderInterface``
     - ``Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate``
     - Use this when you want to inspect or compose the provider pipeline
   * - ``FastForward\EventDispatcher\EventDispatcher``
     - Concrete dispatcher
     - Use this when you prefer the concrete class directly

Registering the service provider through configuration
------------------------------------------------------

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use FastForward\Container\ContainerInterface;
   use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;

   $config = new ArrayConfig([
       ContainerInterface::class => [
           EventDispatcherServiceProvider::class,
       ],
   ]);

Registering the service provider directly
-----------------------------------------

You can also pass the service provider instance when building the container.

.. code-block:: php

   use FastForward\Config\ArrayConfig;
   use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;

   use function FastForward\Container\container;

   $container = container(
       new ArrayConfig([]),
       new EventDispatcherServiceProvider(),
   );

Which Interface Should You Request?
-----------------------------------

Use ``Psr\EventDispatcher\EventDispatcherInterface`` when your code only needs standard PSR-14 dispatch.

Use ``Symfony\Contracts\EventDispatcher\EventDispatcherInterface`` or the concrete
``FastForward\EventDispatcher\EventDispatcher`` when you want this package's optional second argument,
``$eventName``.

.. tip::

   A good default for application code is to depend on the PSR interface. Switch to the Symfony contracts
   interface only in places where named events are part of your design.
