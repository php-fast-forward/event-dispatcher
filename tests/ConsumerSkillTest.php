<?php

declare(strict_types=1);

/**
 * Fast-Forward PSR-14  Event Dispatcher is a simple and fast event dispatcher for PHP.
 *
 * This file is part of fast-forward/event-dispatcher project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://php-fast-forward.github.io/event-dispatcher/
 * @see      https://github.com/php-fast-forward/event-dispatcher
 * @see      https://github.com/php-fast-forward/event-dispatcher/issues
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\EventDispatcher\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

#[CoversNothing]
final class ConsumerSkillTest extends TestCase
{
    /**
     * @return void
     */
    public function testBundledConsumerIntegration(): void
    {
        $root = \dirname(__DIR__);
        $process = new Process([
            \PHP_BINARY,
            $root . '/resources/skills/fast-forward-event-dispatcher/examples/integration.php',
            $root . '/vendor/autoload.php',
        ], $root, [
            'XDEBUG_MODE' => 'off',
        ]);
        $process->setTimeout(20);
        $process->run();

        self::assertTrue($process->isSuccessful(), $process->getErrorOutput() . $process->getOutput());
        self::assertStringContainsString('fast-forward-event-dispatcher: ok', $process->getOutput());
    }
}
