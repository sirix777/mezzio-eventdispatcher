<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use Webware\Event\ConfigKey;
use Webware\Event\ListenerPriority;

final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies'                => $this->getDependencies(),
            ConfigKey::Listeners->value   => $this->getListeners(),
            ConfigKey::Subscribers->value => $this->getSubscribers(),
        ];
    }

    /**
     * @return array<string, array<string, string|class-string|callable>>
     */
    private function getDependencies(): array
    {
        return [
            'invokables' => [
                ListenerOne::class    => ListenerOne::class,
                ListenerTwo::class    => ListenerTwo::class,
                ListenerThree::class  => ListenerThree::class,
                TestSubscriber::class => TestSubscriber::class,
            ],
            'factories'  => [
                'priority.high'   => fn() => new PriorityTrackingListener('high'),
                'priority.normal' => fn() => new PriorityTrackingListener('normal'),
                'priority.low'    => fn() => new PriorityTrackingListener('low'),
            ],
        ];
    }

    /**
     * @return array<int, array{event: string, listener: string|class-string, priority: int}>
     */
    private function getListeners(): array
    {
        return [
            [
                'event'    => 'some.event.name',
                'listener' => ListenerOne::class,
                'priority' => ListenerPriority::High->value,
            ],
            [
                'event'    => 'multiple.listeners.event',
                'listener' => ListenerOne::class,
                'priority' => ListenerPriority::Normal->value,
            ],
            [
                'event'    => 'multiple.listeners.event',
                'listener' => ListenerTwo::class,
                'priority' => ListenerPriority::Normal->value,
            ],
            [
                'event'    => 'multiple.listeners.event',
                'listener' => ListenerThree::class,
                'priority' => ListenerPriority::Low->value,
            ],
            [
                'event'    => 'priority.test.event',
                'listener' => 'priority.high',
                'priority' => ListenerPriority::High->value,
            ],
            [
                'event'    => 'priority.test.event',
                'listener' => 'priority.normal',
                'priority' => ListenerPriority::Normal->value,
            ],
            [
                'event'    => 'priority.test.event',
                'listener' => 'priority.low',
                'priority' => ListenerPriority::Low->value,
            ],
        ];
    }

    /**
     * @return array<int, class-string>
     */
    private function getSubscribers(): array
    {
        return [
            TestSubscriber::class,
        ];
    }
}
