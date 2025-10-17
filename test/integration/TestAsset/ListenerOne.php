<?php

declare(strict_types=1);

namespace Axleus\EventIntegrationTest\TestAsset;

final class ListenerOne
{
    public function __invoke(object $event): void
    {
        $event->calls[] = self::class;
    }
}
