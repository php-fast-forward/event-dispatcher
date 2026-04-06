Installation
============

Requirements
------------

The package currently requires:

- PHP ``^8.3``
- Composer

It also depends on a few standard interoperability packages:

- ``psr/event-dispatcher`` for PSR-14 contracts
- ``psr/container`` for container interoperability
- ``psr/log`` for the bundled error logging listener
- ``symfony/event-dispatcher-contracts`` for Symfony-compatible dispatch signatures

Install With Composer
---------------------

.. code-block:: bash

   composer require fast-forward/event-dispatcher

What You Get After Installation
-------------------------------

After installation, the package gives you:

- ``FastForward\EventDispatcher\EventDispatcher``: the concrete dispatcher
- ``FastForward\EventDispatcher\Event\NamedEvent``: a wrapper for explicit event names
- ``FastForward\EventDispatcher\Event\ErrorEvent``: a dispatchable event representing listener failures
- ``FastForward\EventDispatcher\Event\Event`` and ``StoppableEventTrait``: helpers for stoppable flows
- ``FastForward\EventDispatcher\ListenerProvider\EventSubscriberListenerProvider``: a Symfony subscriber adapter
- ``FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider``: Fast Forward container integration

Choose Your Integration Style
-----------------------------

There are two common ways to use the package.

Direct construction
   Use this when you already have a ``ListenerProviderInterface`` implementation, or when you want a very small
   setup with no container involved.

Fast Forward container integration
   Use this when you want to register listeners declaratively and let the package classify subscribers,
   attributes, custom providers, and callables for you.

.. note::

   Attribute-based listener discovery in this package is performed by the service-provider integration. If you
   instantiate the dispatcher manually, attributes are not scanned automatically.

Recommended First Read
----------------------

If this is your first contact with the library, read these pages next:

- :doc:`quickstart`
- :doc:`../usage/getting-services`
- :doc:`../usage/use-cases`
