Getting Started
===============

This section helps you install the package, understand its moving parts, and run a first successful dispatch.

The fastest mental model is:

1. Create an event object.
2. Register one or more listeners.
3. Dispatch the event.
4. Let the dispatcher resolve listeners from a provider.

If you are new to event-driven code, keep one idea in mind: this package does not require a framework. Events
are just PHP objects, and listeners are just callables that receive those objects.

.. toctree::
   :maxdepth: 1

   installation
   quickstart
