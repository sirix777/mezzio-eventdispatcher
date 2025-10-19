<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;
use Webware\Event\EventInterface;
use Webware\Event\ListenerPriority;

use function count;

final class TestSubscriber implements ListenerSubscriber
{
    /** @var array<string> */
    public array $invocations = [];

    public function subscribeListeners(ListenerRegistry $acceptor): void
    {
        $acceptor->subscribeTo(
            'subscriber.event',
            fn(EventInterface $event) => $this->handleEvent($event),
            ListenerPriority::Normal->value
        );

        $acceptor->subscribeTo(
            'another.subscriber.event',
            fn(EventInterface $event) => $this->handleEvent($event),
            ListenerPriority::High->value
        );
    }

    private function handleEvent(EventInterface $event): void
    {
        $this->invocations[] = $event->getName();
    }

    public function wasInvoked(): bool
    {
        return count($this->invocations) > 0;
    }

    /** @return array<string> */
    public function getInvocations(): array
    {
        return $this->invocations;
    }
}
