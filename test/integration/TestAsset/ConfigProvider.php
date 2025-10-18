<?php

declare(strict_types=1);

namespace Webware\EventIntegrationTest\TestAsset;

use Webware\Event\ListenerPriority;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'listeners' => [
                [
                    'event'    => 'some.event.name',
                    'listener' => ListenerOne::class,
                    'priority' => ListenerPriority::High->value,
                ],
            ],
        ];
    }
}