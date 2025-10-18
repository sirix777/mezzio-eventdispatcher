<?php

declare(strict_types=1);

namespace Webware\EventIntegrationTest\TestAsset;

final class ListenerOne
{
    public function __invoke(object $event): string
    {
        return self::class;
    }
}
