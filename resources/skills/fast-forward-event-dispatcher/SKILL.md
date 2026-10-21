---
name: fast-forward-event-dispatcher
description: Configure Fast Forward PSR-14 listeners and Symfony-compatible dispatch, diagnose listener failures, and use a standalone listener provider.
license: MIT; see LICENSE
compatibility: Supports fast-forward/event-dispatcher ^1.1 and PHP ^8.3. Requires Composer and declared extensions; inspect the installed version. Earlier releases are outside this contract.
metadata:
  author: Fast Forward
  api-baseline: fast-forward/event-dispatcher ^1.1
---

# Dispatch application events

Use this skill for an application consuming `fast-forward/event-dispatcher:^1.1`.
Read its instructions, manifest/lockfile and installed source before editing.
Run `composer show fast-forward/event-dispatcher`. For versions outside this contract,
inspect matching documentation; do not silently upgrade the application.

## First working integration

Requires PHP ^8.3, Composer's application autoloader and this package's runtime
dependencies.
With authority to install the dependency:

```sh
composer require 'fast-forward/event-dispatcher:^1.1'
```

Create `app.php` in the application root alongside composer.json/vendor:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use function FastForward\Container\container;

$seen = [];
$config = new ArrayConfig([
    ContainerInterface::class => [EventDispatcherServiceProvider::class],
    ListenerProviderInterface::class => [
        static function (stdClass $event) use (&$seen): void { $seen[] = $event->name; },
    ],
]);
$container = container($config);
$event = (object) ['name' => 'created'];
$returned = $container->get(EventDispatcherInterface::class)->dispatch($event);
if ($returned !== $event || $seen !== ['created']) {
    throw new RuntimeException('Configured listener did not receive the original event.');
}
$provider = new \Phly\EventDispatcher\ListenerProvider\ReflectionBasedListenerProvider();
$provider->listen(static function (stdClass $event): never {
    throw new RuntimeException('listener fixture failure');
});
$dispatcher = new \FastForward\EventDispatcher\EventDispatcher($provider);

echo "integration ready\n";
```

Run `php app.php`. It throws on a wrong result; success prints
`integration ready`. It uses no production data and starts no server.

## Wiring and failure boundaries

Define the raw provider list under `FastForward\Container\ContainerInterface::class` and listener entries under `Psr\EventDispatcher\ListenerProviderInterface::class`. Pass ArrayConfig to `container($config)`; ConfigContainer exposes raw entries through `config.`-prefixed ids and returns the whole config for ConfigInterface::class. Listeners require a typed first event parameter; untyped/parameterless listeners are rejected. A configured class listener must resolve as a callable from the application container.

Prefer this configuration route for applications. For standalone dispatch construct `FastForward\EventDispatcher\EventDispatcher` with any PSR-14 ListenerProviderInterface, as in the negative case. No Fast Forward container is required for that direct route.

Configured class listeners can use `Symfony\Component\EventDispatcher\Attribute\AsEventListener` and EventSubscriberInterface only when the application explicitly installs `symfony/event-dispatcher:^7.4`. Symfony contracts alone do not supply those component classes. Attribute priorities are handled by the prioritized provider; subscriber methods use declared event names/priorities. Do not infer priority ordering across unrelated provider strategies.

Put application service overrides in an earlier ArrayServiceProvider; the first source successfully resolving an id wins, while separate providers inside an AggregateServiceProvider merge their maps in provider order. ServiceProviderContainer caches dispatcher/provider instances; configure listeners before resolving them.

dispatch runs listeners for the original object, then a NamedEvent wrapper unless propagation stopped. It returns the original event. Named/wildcard listeners may observe both stages: avoid duplicate side effects. A listener throwable emits ErrorEvent and is rethrown. An ErrorEvent listener failure rethrows the original error, avoiding recursive error dispatch. Error listeners observe failures rather than swallowing them. Use Event or StoppableEventTrait when propagation control is required, and LogErrorEventListener with an application-supplied PSR-3 logger for diagnostics.

The helper tries aggregate sources in registration order. A PSR container or
not-found exception from one source permits fallback to another source; other
throwables propagate. Source priority is therefore the first successful resolution,
not a guarantee that an earlier broken factory terminates lookup. Nested provider
list errors during helper composition are caught; if services are absent, inspect
the raw configuration and resolve the provider directly to diagnose the failure.

## Verify integration

Run the [complete example](examples/integration.php) against the application's
autoloader in a disposable process. It checks success and negative cases:

```sh
php /path/to/fast-forward-event-dispatcher/examples/integration.php ./vendor/autoload.php
```

Expected final marker: `fast-forward-event-dispatcher: ok`. Keep application-specific regression
tests in its existing PHPUnit suite, with each fixture class in its same-named
file. File copying, host discovery and completed agent tasks are different checks.
For optional copying/synchronization load
[installation and lifecycle](references/installation.md).
