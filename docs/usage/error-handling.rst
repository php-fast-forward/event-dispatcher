Error Handling
==============

When a listener throws, the dispatcher emits ``FastForward\EventDispatcher\Event\ErrorEvent`` and then rethrows
the original exception.

Why This Design Exists
----------------------

This lets your application do two things at once:

- observe and log failures through regular event listeners;
- still fail loudly in the main execution flow instead of silently swallowing errors.

Basic Flow
----------

1. A listener handling your original event throws ``Throwable``.
2. The dispatcher creates ``ErrorEvent($originalEvent, $listener, $throwable)``.
3. The dispatcher dispatches that ``ErrorEvent``.
4. After that dispatch finishes, the original throwable is rethrown.

Logging Example
---------------

.. code-block:: php

   use FastForward\EventDispatcher\Event\ErrorEvent;
   use FastForward\EventDispatcher\Listener\LogErrorEventListener;
   use Psr\Log\AbstractLogger;
   use Psr\Log\LoggerInterface;

   final class StdoutLogger extends AbstractLogger
   {
       public function log($level, \Stringable|string $message, array $context = []): void
       {
           echo '[' . strtoupper((string) $level) . '] ' . $message . PHP_EOL;

           if (($context['exception'] ?? null) instanceof Throwable) {
               echo 'Exception: ' . $context['exception']->getMessage() . PHP_EOL;
           }

           if (($context['event'] ?? null) instanceof ErrorEvent) {
               echo 'Original event: ' . $context['event']->getEvent()::class . PHP_EOL;
           }
       }
   }

``LogErrorEventListener`` is a ready-made listener that forwards the error details to a PSR-3 logger.

What You Can Read From ErrorEvent
---------------------------------

``ErrorEvent`` exposes:

- ``getEvent()``: the original event that was being dispatched;
- ``getListener()``: the listener that threw;
- ``getThrowable()``: the original throwable.

Can ErrorEvent Stop the Failure?
--------------------------------

No. ``ErrorEvent`` can stop the propagation of other error listeners, but it does not absorb the original
exception. The dispatcher always rethrows the original throwable after error dispatch completes.

Recursive Error Protection
--------------------------

If a listener handling ``ErrorEvent`` throws, the dispatcher rethrows the original throwable carried by the
``ErrorEvent``. This avoids infinite recursion in the error pipeline.

Recommended Strategy
--------------------

- Use ``ErrorEvent`` listeners for logging, metrics, tracing, alerts, or cleanup.
- Catch exceptions around ``dispatch()`` when your application must decide what to do next.
- Do not expect an error listener to turn a failing dispatch into a successful one.
