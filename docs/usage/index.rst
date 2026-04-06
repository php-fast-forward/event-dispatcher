Usage Guide
===========

This section focuses on the day-to-day tasks you are most likely to perform with the package.

You can use the dispatcher in several styles:

- plain typed events dispatched through the PSR-14 interface;
- named events dispatched through the Symfony contracts interface or the concrete dispatcher;
- Symfony subscribers;
- attribute-based listeners;
- wildcard listener providers for cross-cutting observers;
- stoppable events;
- error observation with ``ErrorEvent``.

.. toctree::
   :maxdepth: 1

   getting-services
   dispatching-events
   named-events
   subscribers
   attribute-listeners
   wildcard-listeners
   stoppable-events
   error-handling
   use-cases
