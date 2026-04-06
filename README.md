# Fast Forward Event Dispatcher

A lightweight PSR-14 event dispatcher for PHP 8.3+ with named events, Symfony-style subscribers,
attribute-based listeners, and first-class integration with the Fast Forward container.

[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-777BB4.svg?logo=php&logoColor=white)](https://www.php.net/releases/)
[![PSR-14](https://img.shields.io/badge/PSR-14-event%20dispatcher-0A7EA4.svg)](https://www.php-fig.org/psr/psr-14/)
[![License: MIT](https://img.shields.io/badge/license-MIT-22C55E.svg)](https://opensource.org/licenses/MIT)
[![Tests](https://github.com/php-fast-forward/event-dispatcher/actions/workflows/tests.yml/badge.svg)](https://github.com/php-fast-forward/event-dispatcher/actions/workflows/tests.yml)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fevent--dispatcher-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/event-dispatcher)

## ✨ Features

- 🚀 PSR-14 dispatcher with support for `Symfony\Contracts\EventDispatcher\EventDispatcherInterface`
- 🏷️ Named events via `dispatch($event, $eventName)` and the `NamedEvent` wrapper
- 🔌 Automatic listener classification inside Fast Forward applications
- 🧩 Support for invokable listeners, Symfony subscribers, attributes, and custom listener providers
- 📊 Priority-aware execution for subscribers and `#[AsEventListener]`
- 🛑 Propagation control through `StoppableEventInterface`, the `Event` base class, and `StoppableEventTrait`
- 🧯 Error instrumentation through `ErrorEvent` and `LogErrorEventListener`
- 🌐 Wildcard listener providers for cross-cutting observers such as logging, metrics, and audit trails
- 🪶 Small surface area with practical defaults and clear extension points

## 📦 Installation

Install the package with Composer:

```bash
composer require fast-forward/event-dispatcher
```

Requirements:

- PHP `^8.3`
- `psr/event-dispatcher`
- `psr/container`
- `psr/log`
- `fast-forward/container`

If you want to register Symfony-style subscribers or use `#[AsEventListener]`, also install:

```bash
composer require symfony/event-dispatcher
```

## 🛠️ Usage

### 1. Dispatch a typed PSR-14 event

The Fast Forward service provider wires the dispatcher and classifies configured listeners by strategy.

```php
<?php

declare(strict_types=1);

use FastForward\Config\ArrayConfig;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

use function FastForward\Container\container;

final readonly class UserRegistered
{
    public function __construct(public string $email) {}
}

final class SendWelcomeEmailListener
{
    public function __invoke(UserRegistered $event): void
    {
        echo 'Sending welcome email to ' . $event->email . PHP_EOL;
    }
}

$config = new ArrayConfig([
    ListenerProviderInterface::class => [
        SendWelcomeEmailListener::class,
    ],
]);

$container = container($config, EventDispatcherServiceProvider::class);
$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new UserRegistered('github@mentordosnerds.com'));
```

Output:

```text
Sending welcome email to github@mentordosnerds.com
```

### 2. Dispatch the same event with an explicit name

Use a named dispatch when you want string-based routing in addition to the event class itself.

```php
<?php

declare(strict_types=1);

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function FastForward\Container\container;

final readonly class PaymentReceived
{
    public function __construct(
        public string $invoiceId,
        public float $amount,
    ) {}
}

final class BillingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'billing.payment_received' => 'onPaymentReceived',
        ];
    }

    public function onPaymentReceived(PaymentReceived $event): void
    {
        echo 'Payment confirmed for invoice ' . $event->invoiceId . PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [
        EventDispatcherServiceProvider::class,
    ],
    ListenerProviderInterface::class => [
        BillingSubscriber::class,
    ],
]);

$container = container($config);
$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(
    new PaymentReceived('INV-2026-0001', 199.90),
    'billing.payment_received',
);
```

### 3. Use priorities with `#[AsEventListener]`

Attribute-based listeners are detected and routed to a priority-aware provider automatically.

```php
<?php

declare(strict_types=1);

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function FastForward\Container\container;

final readonly class OrderConfirmed
{
    public function __construct(public string $orderNumber) {}
}

final class ReserveStockListener
{
    #[AsEventListener(priority: 100)]
    public function __invoke(OrderConfirmed $event): void
    {
        echo 'Reserving stock for ' . $event->orderNumber . PHP_EOL;
    }
}

final class NotifyOperationsListener
{
    #[AsEventListener(priority: 10)]
    public function __invoke(OrderConfirmed $event): void
    {
        echo 'Notifying operations about ' . $event->orderNumber . PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [
        EventDispatcherServiceProvider::class,
    ],
    ListenerProviderInterface::class => [
        NotifyOperationsListener::class,
        ReserveStockListener::class,
    ],
]);

$container = container($config);
$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new OrderConfirmed('PED-2026-0042'));
```

### 4. Observe listener failures with `ErrorEvent`

When a listener throws, the dispatcher emits an `ErrorEvent` and then rethrows the original exception.
This gives you a clean place to log, trace, or notify without swallowing the failure.

```php
<?php

declare(strict_types=1);

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\EventDispatcher\Listener\LogErrorEventListener;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

use function FastForward\Container\container;

final readonly class ImportReportRequested
{
    public function __construct(public string $reportId) {}
}

final class FailingImportListener
{
    public function __invoke(ImportReportRequested $event): void
    {
        throw new RuntimeException('Failed to generate the report ' . $event->reportId);
    }
}

final class StdoutLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        echo '[' . strtoupper((string) $level) . '] ' . $message . PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [
        EventDispatcherServiceProvider::class,
    ],
    ListenerProviderInterface::class => [
        FailingImportListener::class,
        LogErrorEventListener::class,
    ],
]);

$container = container(
    $config,
    new ArrayServiceProvider([
        LoggerInterface::class => static fn(): LoggerInterface => new StdoutLogger(),
    ]),
);

$dispatcher = $container->get(EventDispatcherInterface::class);

try {
    $dispatcher->dispatch(new ImportReportRequested('REL-2026-0007'));
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}
```

### 5. Log every dispatched object with a wildcard listener provider

`WildcardListenerProvider` is a small base class for providers that should observe every dispatched object.
`LogEventListenerProvider` builds on it and sends each dispatch to a PSR-3 logger, including `NamedEvent`
wrappers produced by named dispatch.

```php
<?php

declare(strict_types=1);

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\EventDispatcher\ListenerProvider\LogEventListenerProvider;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function FastForward\Container\container;

final readonly class UserRegistered
{
    public function __construct(public string $email) {}
}

final class StdoutLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        echo '[' . strtoupper((string) $level) . '] ' . $message . PHP_EOL;
        echo 'Event class: ' . $context['event_class'] . PHP_EOL;

        if (isset($context['event_name'])) {
            echo 'Event name: ' . $context['event_name'] . PHP_EOL;
        }

        if (isset($context['wrapped_event_class'])) {
            echo 'Wrapped event class: ' . $context['wrapped_event_class'] . PHP_EOL;
        }

        echo PHP_EOL;
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [
        EventDispatcherServiceProvider::class,
    ],
    ListenerProviderInterface::class => [
        LogEventListenerProvider::class,
    ],
]);

$container = container(
    $config,
    new ArrayServiceProvider([
        LoggerInterface::class => static fn(): LoggerInterface => new StdoutLogger(),
    ]),
);

$dispatcher = $container->get(EventDispatcherInterface::class);

$dispatcher->dispatch(new UserRegistered('github@mentordosnerds.com'), 'users.registered');
```

Output:

```text
[INFO] Event dispatched
Event class: UserRegistered

[INFO] Event dispatched
Event class: FastForward\EventDispatcher\Event\NamedEvent
Event name: users.registered
Wrapped event class: UserRegistered
```

See [`examples/06-log-all-events.php`](examples/06-log-all-events.php) for the runnable version.

### 6. Understand the dispatch flow

For each dispatched object, the library follows this sequence:

1. Resolve listeners for the original event object.
2. Invoke listeners until propagation stops or a listener throws.
3. If the event is not already a `NamedEvent`, dispatch a named wrapper using the explicit name or the event class.
4. If a listener throws, emit `ErrorEvent`.
5. Rethrow the original throwable after error listeners have been notified.

## 🧰 API Summary

| Class / Interface | Purpose |
| --- | --- |
| `EventDispatcher` | Main dispatcher that runs listeners, dispatches named wrappers, and emits `ErrorEvent` |
| `Event\NamedEvent` | Wraps an event with an explicit dispatch name |
| `Event\Event` | Generic Symfony-compatible base event with public `stopPropagation()` |
| `Event\StoppableEventTrait` | Reusable propagation state for your own event classes |
| `Event\ErrorEvent` | Error envelope emitted when a listener throws |
| `Listener\LogErrorEventListener` | PSR-3 logger integration for `ErrorEvent` |
| `ListenerProvider\WildcardListenerProvider` | Base class for providers that should observe every dispatched object |
| `ListenerProvider\LogEventListenerProvider` | PSR-3 logging provider for all dispatched objects, named wrappers, and error events |
| `ListenerProvider\EventSubscriberListenerProvider` | Adapts Symfony `EventSubscriberInterface` to PSR-14 |
| `ServiceProvider\EventDispatcherServiceProvider` | Registers dispatcher services and config-driven provider extensions |
| `ServiceProvider\Configuration\ConfiguredListenerProviderCollection` | Classifies configured listeners into provider strategies |

## 🔌 Integration

This package fits well in two modes:

- Fast Forward mode: register `EventDispatcherServiceProvider::class` and declare listeners in
  `ListenerProviderInterface::class`.
- Standalone mode: instantiate `EventDispatcher` manually with any `Psr\EventDispatcher\ListenerProviderInterface`.

Out of the box, the Fast Forward integration understands these listener styles:

- Invokable listeners and callable listeners resolved by reflection
- `#[AsEventListener]` classes and public methods
- Symfony `EventSubscriberInterface` subscribers
- Custom listener providers implementing `ListenerProviderInterface`, including wildcard providers such as
  `LogEventListenerProvider`

## 📊 Listener Registration Styles

| Style | Example registration | Best for | Priority support |
| --- | --- | --- | --- |
| Invokable listener | `SendWelcomeEmailListener::class` | Simple one-event listeners | Via provider order |
| `#[AsEventListener]` | `ReserveStockListener::class` | Declarative listeners with local metadata | ✅ |
| Symfony subscriber | `BillingSubscriber::class` | One class handling multiple events | ✅ |
| Wildcard provider | `LogEventListenerProvider::class` | Cross-cutting logging, metrics, and auditing | Provider-defined |
| Custom provider | `DelegatingListenerProvider::class` | Advanced routing, delegation, composition | Provider-defined |

## 📁 Directory Structure Example

```text
.
├── composer.json
├── examples/
│   ├── 01-dispatch-psr14.php
│   ├── 02-dispatch-named-event.php
│   ├── 03-dispatch-event-subscriber.php
│   ├── 04-dispatch-as-event-listener-attribute.php
│   ├── 05-psr-log-error-handling.php
│   └── 06-log-all-events.php
├── src/
│   ├── Event/
│   ├── Exception/
│   ├── Listener/
│   ├── ListenerProvider/
│   └── ServiceProvider/
└── tests/
```

## ⚙️ Advanced and Customization

### Mix listener strategies in one configuration

The service provider classifies each configured item and routes it to the matching provider. That means
you can combine plain callables, attributed listeners, subscribers, and custom providers in the same app.

### Create stoppable domain events

```php
<?php

declare(strict_types=1);

use FastForward\EventDispatcher\Event\Event;

final class InventoryReservationRequested extends Event
{
    public function __construct(public string $sku) {}
}
```

### Build wildcard observers

When you need one listener-provider to observe every dispatched object, extend
`ListenerProvider\WildcardListenerProvider`. This is useful for logging, metrics, auditing, and other
cross-cutting concerns that should not depend on one event class.

### Choose between `Event` and `StoppableEventTrait`

Use `FastForward\EventDispatcher\Event\Event` when you want a generic base class compatible with Symfony's
event contract and you are fine with a public `stopPropagation()` method.

Use `Event\StoppableEventTrait` when you need the same stoppable behavior but your event already extends
another class and cannot inherit from `Event`.

### Log failures without hiding them

`LogErrorEventListener` is intentionally observational. It helps you record failures, but the dispatcher
still rethrows the original exception so your application can fail fast when it should.

## 🛠️ Versioning and Breaking Changes

- The Composer branch alias is currently `1.x-dev`.
- The package targets modern PHP `8.3+`.
- No public breaking-change matrix is documented yet; review release notes and changelog entries as the
  package evolves.

## ❓ FAQ

**Do I need the Fast Forward container to use this package?**

No. The core `EventDispatcher` only needs a PSR-14 `ListenerProviderInterface`. The Fast Forward service
provider is the convenience layer that makes configuration and listener discovery ergonomic.

**When should I use named events?**

Use named events when your application already relies on string-based event identifiers, or when you want
to expose a stable semantic name that is different from the PHP class name.

**Do Symfony subscribers and attributes work out of the box?**

They work with this package, but they rely on Symfony's event-dispatcher component types. If your project
does not already include them, add `symfony/event-dispatcher`.

**What happens if a listener throws an exception?**

The dispatcher emits `ErrorEvent`, gives your error listeners a chance to observe the failure, and then
rethrows the original exception.

**How do I log every dispatched event?**

Register `LogEventListenerProvider` or create your own subclass of `WildcardListenerProvider`. Wildcard
providers receive the original event object and the generated `NamedEvent` wrapper when named dispatch is
used.

## 🛡️ License

This library is released under the [MIT License](https://opensource.org/licenses/MIT).

Copyright (c) 2025-2026 Felipe Sayão Lobato Abreu.

## 🤝 Contributing

Contributions, issues, and pull requests are welcome.

Useful local commands:

```bash
composer dev-tools
composer dev-tools:fix
```

When changing behavior, keep the examples and this README in sync with the code.

## 🔗 Links

- [Repository](https://github.com/php-fast-forward/event-dispatcher)
- [Issues](https://github.com/php-fast-forward/event-dispatcher/issues)
- [Packagist](https://packagist.org/packages/fast-forward/event-dispatcher)
- [PSR-14: Event Dispatcher](https://www.php-fig.org/psr/psr-14/)
- [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/)
- [Symfony Event Subscribers](https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber)
- [Examples directory](examples)
