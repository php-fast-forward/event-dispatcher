Troubleshooting
===============

This page covers the most common problems new users face when wiring the package for the first time.

My listener is not being called
-------------------------------

Check these points first:

- Is the listener registered under ``Psr\EventDispatcher\ListenerProviderInterface::class``?
- Does the listener's first parameter match the event class or explicit event name path you are using?
- If it is an attributed listener, did you register ``EventDispatcherServiceProvider``?
- If it is a reflection-based listener, is the first parameter typed?

My named event listener never runs
----------------------------------

You probably dispatched through code typed only against ``Psr\EventDispatcher\EventDispatcherInterface`` and did
not pass an explicit name.

Use:

- ``Symfony\Contracts\EventDispatcher\EventDispatcherInterface``; or
- ``FastForward\EventDispatcher\EventDispatcher``.

Then call ``dispatch($event, 'your.event.name')``.

My listener runs twice
----------------------

This usually happens when the same logic is effectively registered for:

- the original event class; and
- the named wrapper dispatch.

Remember that named dispatch performs both passes.

Bootstrap fails with RuntimeException
-------------------------------------

Inspect the configured listener declaration. Common causes are:

- unsupported value shape;
- listener method without parameters;
- listener parameter without a type;
- attributed listener method without enough information to infer the event type.

Bootstrap fails with InvalidArgumentException
---------------------------------------------

This usually means a class string was registered as a subscriber but does not implement
``Symfony\Component\EventDispatcher\EventSubscriberInterface``.

My stoppable event never stops propagation
------------------------------------------

Make sure:

- your event implements ``StoppableEventInterface`` directly or through ``Event`` / ``StoppableEventTrait``;
- your code or one of your listeners actually calls ``stopPropagation()`` when the stop condition is met;
- you are not expecting propagation stop to undo work already performed by earlier listeners.

The error was logged, but the exception still crashed my flow
-------------------------------------------------------------

That is expected behavior. ``ErrorEvent`` is for observation, not suppression. Catch the exception around
``dispatch()`` if your application needs a fallback path.

My attribute listener is ignored during direct manual setup
-----------------------------------------------------------

Attribute scanning is part of the service-provider configuration flow. Manual construction of
``EventDispatcher`` does not scan attributes automatically.
