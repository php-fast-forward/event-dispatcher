<?php

declare(strict_types=1);

require $argv[1] ?? dirname(__DIR__, 4) . '/vendor/autoload.php';

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
try {
    $dispatcher->dispatch($event);
    throw new RuntimeException('Listener failure unexpectedly swallowed.');
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'listener fixture failure') { throw $error; }
}

echo "fast-forward-event-dispatcher: ok\n";
