<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\EventDispatcher;
use League\Event\PrioritizedListenerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @phpstan-ignore trait.unused
 */
trait EventDispatcherAwareTrait
{
    private readonly ?EventDispatcherInterface $eventDispatcher;

    public function useEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->useEventDispatcher($eventDispatcher);
    }

    public function eventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new EventDispatcher(new PrioritizedListenerRegistry());
        }
        return $this->eventDispatcher;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher();
    }
}
