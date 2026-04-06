Fast Forward Event Dispatcher
=============================

``fast-forward/event-dispatcher`` is a lightweight event dispatcher package for PHP applications that want
PSR-14 interoperability, Symfony subscriber compatibility, and straightforward Fast Forward container
integration.

This package is especially useful when you want to:

- dispatch plain PHP objects as domain or application events;
- keep listener registration explicit and testable;
- mix PSR-14 listeners, Symfony subscribers, named events, and attribute-based listeners in one application;
- add wildcard listeners for cross-cutting concerns such as logging, metrics, or audit trails;
- observe listener failures through an ``ErrorEvent`` while still rethrowing the original exception.

Useful links
------------

- GitHub repository: https://github.com/php-fast-forward/event-dispatcher
- Issue tracker: https://github.com/php-fast-forward/event-dispatcher/issues
- Packagist: https://packagist.org/packages/fast-forward/event-dispatcher
- PSR-14 specification: https://www.php-fig.org/psr/psr-14/
- Symfony Event Dispatcher contracts: https://github.com/symfony/event-dispatcher-contracts

What Makes This Package Different
---------------------------------

- It dispatches typed object events through any PSR-14 listener provider.
- It can also dispatch the same event a second time as a named wrapper, which makes string-based event names
  possible without giving up object events.
- It ships a service provider that automatically classifies configured listeners into the right runtime
  strategy.
- It includes a small wildcard-provider base class for cross-cutting observers that should see every
  dispatched object.
- It supports Symfony ``EventSubscriberInterface`` and ``AsEventListener`` attributes without turning your
  whole application into a Symfony application.

Documentation Map
-----------------

.. toctree::
   :maxdepth: 2
   :caption: Contents

   getting-started/index
   usage/index
   advanced/index
   api/index
   links/index
   faq
   compatibility
