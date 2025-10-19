<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use Webware\Event\EventInterface;
use Webware\Event\ListenerInterface;

use function count;

final class ListenerThree implements ListenerInterface
{
    /** @var array<string> */
    public array $invocations = [];

    public function __invoke(EventInterface $event): void
    {
        $this->invocations[] = $event->getName();
    }

    public function wasInvoked(): bool
    {
        return count($this->invocations) > 0;
    }

    public function getInvocationCount(): int
    {
        return count($this->invocations);
    }
}
