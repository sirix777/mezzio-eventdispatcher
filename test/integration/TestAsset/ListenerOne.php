<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use Webware\Event\EventInterface;

final class ListenerOne
{
    public function __invoke(EventInterface $event): string
    {
        return $event->getName();
    }
}
