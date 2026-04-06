Extension Points
================

The package stays intentionally small, but it still offers several clean places for extension.

1. Bring Your Own Listener Provider
-----------------------------------

Because ``EventDispatcher`` only depends on ``ListenerProviderInterface``, you can plug in any PSR-14 provider.

This is the main extension point if your application already has custom lookup rules.

2. Build Wildcard Observers
---------------------------

``WildcardListenerProvider`` is a convenient base class when one provider should receive every dispatched
object.

Typical uses:

- event logging;
- metrics;
- audit trails;
- tracing.

3. Attach Additional Providers Through Configuration
----------------------------------------------------

With the service provider integration, classes implementing ``ListenerProviderInterface`` can be listed under
the configuration key ``Psr\EventDispatcher\ListenerProviderInterface::class``.

Those providers are attached to the aggregate provider after the built-in strategies.

4. Add Error Observers
----------------------

``ErrorEvent`` makes failure observation extensible. You can attach listeners for:

- logging;
- metrics;
- tracing;
- notifications;
- cleanup tasks.

5. Build Rich Stoppable Events
------------------------------

``StoppableEventTrait`` is a small building block for events that need stoppable behavior but already extend
another base class.

6. Mix Listener Styles Gradually
--------------------------------

A useful extension strategy is evolutionary rather than technical:

- keep old Symfony subscribers;
- add new invokable listeners;
- introduce attributed listeners where they improve readability;
- attach a custom provider when one bounded context needs special lookup behavior.

What This Package Does Not Expose
---------------------------------

The current codebase does not expose:

- singleton accessors;
- facade-style static helpers;
- alias maps beyond container service aliases for the dispatcher interfaces.

That is a design choice. Composition happens through constructors and the container, not through global state.
