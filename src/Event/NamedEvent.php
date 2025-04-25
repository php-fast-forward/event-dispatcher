<?php

declare(strict_types=1);

/**
 * This file is part of php-fast-forward/event-dispatcher.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @link      https://github.com/php-fast-forward/event-dispatcher
 * @copyright Copyright (c) 2025 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\EventDispatcher\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class NamedEvent extends Event
{
    public function __construct(
        private string $name,
        private object $event,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getEvent(): object
    {
        return $this->event;
    }
}
