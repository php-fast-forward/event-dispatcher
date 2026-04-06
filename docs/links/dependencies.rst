Dependencies
============

Runtime Dependencies
--------------------

.. list-table::
   :header-rows: 1

   * - Package
     - Why it is here
   * - ``container-interop/service-provider``
     - service-provider contract used by the package integration
   * - ``fast-forward/container``
     - recommended container integration path for this package
   * - ``fast-forward/iterators``
     - support utilities used in the wider dispatch stack
   * - ``phly/phly-event-dispatcher``
     - aggregate provider, prioritized provider, reflection provider, and lazy listeners
   * - ``psr/container``
     - container interoperability contracts
   * - ``psr/event-dispatcher``
     - PSR-14 dispatcher and listener-provider contracts
   * - ``psr/log``
     - logger contract used by ``LogErrorEventListener``
   * - ``symfony/event-dispatcher-contracts``
     - Symfony-compatible dispatcher interface and subscriber ecosystem alignment

Development Dependency
----------------------

.. list-table::
   :header-rows: 1

   * - Package
     - Why it is here
   * - ``fast-forward/dev-tools``
     - development tooling used by the repository

Dependency Notes
----------------

- ``psr/log`` is always installed because the package ships a PSR-3-aware error logging listener.
- ``phly/phly-event-dispatcher`` provides much of the low-level provider machinery used by the container
  integration.
- The package itself stays small because it relies on standardized contracts and a few focused support
  libraries.
