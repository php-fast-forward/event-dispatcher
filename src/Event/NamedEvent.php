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

/**
 * Class NamedEvent.
 *
 * This class decorates a domain event by associating it with an explicit string name.
 * It SHALL be used in cases where events must be dispatched or identified by a specific
 * name in addition to their object type.
 *
 * NamedEvent enables event listeners to react not only to the class of an event but
 * also to a string-based identifier, providing more flexible routing and handling
 * strategies in event-driven architectures.
 *
 * @package FastForward\EventDispatcher\Event
 */
final class NamedEvent extends Event
{
    /**
     * @var string The name explicitly associated with the event.
     *             This property MUST represent the identifier under which the event is dispatched.
     */
    private string $name;

    /**
     * @var object The original domain event instance being wrapped.
     *             This event MUST NOT be null and SHOULD be treated as immutable if possible.
     */
    private object $event;

    /**
     * Constructs a NamedEvent instance.
     *
     * This constructor MUST be provided with both a string name and the original event object.
     *
     * @param object      $event the original event instance being wrapped
     * @param null|string $name  the explicit name to associate with the event
     */
    public function __construct(object $event, ?string $name = null)
    {
        $this->event = $event;
        $this->name  = $name ?? \get_class($event);
    }

    /**
     * Retrieves the explicit name of the wrapped event.
     *
     * @return string the name assigned to the event
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the original domain event instance.
     *
     * @return object the original event object wrapped by this instance
     */
    public function getEvent(): object
    {
        return $this->event;
    }
}
