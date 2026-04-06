FAQ
===

What kind of events can I dispatch?
-----------------------------------

Any PHP object can be dispatched. In practice, most projects use small domain event classes.

Do I need a framework to use this package?
------------------------------------------

No. You can instantiate ``EventDispatcher`` directly with any PSR-14 listener provider.

When should I use the Fast Forward service provider?
----------------------------------------------------

Use it when you want declarative listener registration, attribute discovery, subscriber support, and lazy
container resolution with minimal boilerplate.

What is the difference between typed events and named events?
-------------------------------------------------------------

Typed events are matched by class. Named events are matched by an explicit string such as
``billing.payment_received``. This package can do both in one dispatch.

Can I use Symfony subscribers here?
-----------------------------------

Yes. ``EventSubscriberListenerProvider`` supports ``EventSubscriberInterface`` and the common
``getSubscribedEvents()`` shapes used by Symfony.

Can I use ``AsEventListener`` attributes?
-----------------------------------------

Yes, but automatic discovery happens through ``EventDispatcherServiceProvider`` and the configuration model. It
is not performed by plain manual dispatcher construction.

How do I stop propagation?
--------------------------

Use ``Event`` when a simple Symfony-compatible base class is enough, or use ``StoppableEventTrait`` when your
event already extends another class. In both cases, listeners can call ``stopPropagation()`` directly.

Why is my exception still thrown even though I registered an error listener?
---------------------------------------------------------------------------

Because ``ErrorEvent`` is observational. It lets listeners log or react to the failure, but the dispatcher still
rethrows the original throwable.

How do I log every dispatched event?
------------------------------------

Register ``LogEventListenerProvider`` or create your own subclass of ``WildcardListenerProvider``. Wildcard
providers receive the original event object and the generated ``NamedEvent`` wrapper when named dispatch is
used.

Can I register closures and functions?
--------------------------------------

Yes, through the service-provider configuration model, as long as the callable has a typed first parameter so
the package can infer the event type.

Can I register my own ``ListenerProviderInterface`` implementation?
-------------------------------------------------------------------

Yes. The dispatcher accepts any PSR-14 provider directly, and the container integration can also attach custom
providers from configuration.

Why does the same listener sometimes run twice?
-----------------------------------------------

Named dispatch performs a typed pass and then a named-wrapper pass. If the same logic is registered for both, it
can execute in both passes.

Does this package provide async events or message queues?
---------------------------------------------------------

No. It is an in-process dispatcher. Async transport, persistence, and retries are outside its current scope.
