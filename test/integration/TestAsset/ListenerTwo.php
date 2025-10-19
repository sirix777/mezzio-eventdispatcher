<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use Webware\Event\EventInterface;
use Webware\Event\ListenerInterface;

final class ListenerTwo implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        // Handle the event
    }
}
