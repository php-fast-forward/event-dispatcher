Compatibility
=============

Runtime Compatibility
---------------------

.. list-table::
   :header-rows: 1

   * - Concern
     - Current expectation
   * - PHP
     - ``^8.3``
   * - Event dispatcher contract
     - ``psr/event-dispatcher`` ``^1.0``
   * - Container contract
     - ``psr/container`` ``^2.0``
   * - Logger contract
     - ``psr/log`` ``^3.0``
   * - Symfony dispatcher contracts
     - ``symfony/event-dispatcher-contracts`` ``^3.0``

Practical Compatibility Notes
-----------------------------

- The package uses modern PHP syntax and should be treated as a PHP 8.3+ library.
- The concrete dispatcher is compatible with PSR-14 and also implements the Symfony contracts dispatcher
  interface.
- Subscriber support follows Symfony's ``EventSubscriberInterface`` conventions.
- Attribute support relies on Symfony's ``AsEventListener`` attribute.

Upgrade Planning Tips
---------------------

When upgrading an application that uses this package:

- verify your PHP runtime first;
- review listener signatures, especially typed first parameters;
- test named event flows if you depend on explicit string event names;
- test error handling paths because ``ErrorEvent`` behavior is part of the contract many applications rely on.
