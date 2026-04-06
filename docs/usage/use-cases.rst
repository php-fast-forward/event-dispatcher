Use Cases
=========

This page catalogs the main scenarios supported by the library. It is the quickest way to understand what the
package can and cannot do.

Scenario Overview
-----------------

.. list-table::
   :header-rows: 1

   * - Scenario
     - Best fit
     - Main building blocks
   * - typed domain events
     - internal application workflows
     - ``EventDispatcher`` plus typed listeners
   * - named integration events
     - stable external event names
     - ``dispatch($event, $name)`` and ``NamedEvent``
   * - Symfony subscribers
     - grouped reactions in one class
     - ``EventSubscriberListenerProvider``
   * - attribute listeners
     - event binding close to the method
     - ``AsEventListener`` plus service provider
   * - prioritized workflows
     - deterministic ordered execution
     - subscriber priorities or attribute priorities
   * - wildcard observers
     - logging, metrics, and audit trails
     - ``WildcardListenerProvider`` and ``LogEventListenerProvider``
   * - stoppable pipelines
     - veto, validation, first-match logic
     - ``Event`` or ``StoppableEventTrait``
   * - error observation
     - logging and metrics around failures
     - ``ErrorEvent`` and ``LogErrorEventListener``
   * - provider composition
     - mixing different listener sources
     - aggregate provider plus custom providers
   * - container-based applications
     - declarative bootstrap
     - ``EventDispatcherServiceProvider``
   * - direct manual integration
     - no container or custom bootstrap
     - ``new EventDispatcher($provider)``

Detailed Scenarios
------------------

1. Dispatching typed domain events
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Use this when your event class name is already expressive enough.

Example domains:

- user registration
- invoice generation
- stock reservation
- subscription lifecycle changes

2. Dispatching the same event by class and by name
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Use this when you want internal listeners to depend on the event class, but integration listeners to depend on
a stable name such as ``billing.payment_received``.

3. Grouping logic in subscribers
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Use this when one class owns several related reactions and you want local priority declarations.

Typical examples:

- onboarding workflows
- order lifecycle orchestration
- audit plus notification plus projection updates

4. Declaring listeners with attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Use this when you want the binding between event and method to live next to the code that handles the event.

This is often comfortable in applications where listeners are regular services.

5. Logging or observing every dispatched object
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Use this when one cross-cutting concern should receive every dispatched object instead of one specific event
class.

Typical examples:

- centralized event logging;
- audit trails;
- metrics and tracing.

6. Stopping propagation after a business veto
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Use this when one listener can determine that no later listeners should continue.

Typical examples:

- permission denied
- duplicate processing prevention
- invalid state detection

7. Observing failures without hiding them
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Use this when you want logs, traces, or alerts around listener failures while still rethrowing the root
exception to the caller.

8. Mixing several registration styles in one application
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The container integration supports a mixed configuration containing:

- custom listener providers;
- Symfony subscribers;
- attributed listeners;
- generic callables and invokable listeners.

9. Migrating from existing Symfony subscriber code
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Because subscriber support is built in, existing subscriber classes can often be reused with little or no
change.

10. Adding a custom listener provider
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If your project already has a provider with its own lookup rules, you can configure it alongside the built-in
strategies and let the aggregate provider consult it too.

11. Running without any framework
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can use this package in scripts, small services, CLI tools, or custom applications. The dispatcher itself
only needs a PSR-14 listener provider.

Scenarios This Package Does Not Target Directly
-----------------------------------------------

This library does not currently provide, by itself:

- asynchronous event transport;
- string-based wildcard event-name patterns such as ``billing.*``;
- automatic listener discovery outside the service-provider configuration path;
- transactional event storage or replay;
- listener result aggregation APIs.

Those are not bugs. They are simply outside the scope of this package's current design.
