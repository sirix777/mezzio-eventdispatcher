<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use League\Event\Listener;
use Webware\Event\ConfigKey;
use Webware\Event\ListenerPriority;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'                => $this->getDependencies(),
            ConfigKey::Listeners->value   => $this->getListeners(),
            ConfigKey::Subscribers->value => [],
        ];
    }

    private function getDependencies(): array
    {
        return [
            'invokables' => [
                ListenerOne::class => ListenerOne::class,
            ],
        ];
    }

    private function getListeners(): array
    {
        return [
            [
                'event'    => 'some.event.name',
                'listener' => ListenerOne::class,
                'priority' => ListenerPriority::High->value,
            ],
        ];
    }
}