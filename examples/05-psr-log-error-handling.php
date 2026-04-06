<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/event-dispatcher.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2025-2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/event-dispatcher
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

use FastForward\Config\ArrayConfig;
use FastForward\Container\ContainerInterface;
use FastForward\Container\ServiceProvider\ArrayServiceProvider;
use FastForward\EventDispatcher\Event\ErrorEvent;
use FastForward\EventDispatcher\Listener\LogErrorEventListener;
use FastForward\EventDispatcher\ServiceProvider\EventDispatcherServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

use function FastForward\Container\container;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class ImportReportRequested
{
    /**
     * @param string $reportId
     */
    public function __construct(
        public string $reportId
    ) {}
}

final class FailingImportListener
{
    /**
     * @param ImportReportRequested $event
     *
     * @return never
     *
     * @throws RuntimeException
     */
    public function __invoke(ImportReportRequested $event): never
    {
        throw new RuntimeException('Failed to generate the report ' . $event->reportId);
    }
}

final class StdoutLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        echo '[' . strtoupper((string) $level) . '] ' . $message . \PHP_EOL;

        if (($context['exception'] ?? null) instanceof Throwable) {
            echo 'Exception: ' . $context['exception']->getMessage() . \PHP_EOL;
        }

        if (($context['event'] ?? null) instanceof ErrorEvent) {
            echo 'Original event: ' . $context['event']->getEvent()::class . \PHP_EOL;
        }
    }
}

$config = new ArrayConfig([
    ContainerInterface::class => [EventDispatcherServiceProvider::class],
    ListenerProviderInterface::class => [FailingImportListener::class, LogErrorEventListener::class],
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
} catch (Throwable $throwable) {
    echo 'The main flow captured the original exception: ' . $throwable->getMessage() . \PHP_EOL;
}
