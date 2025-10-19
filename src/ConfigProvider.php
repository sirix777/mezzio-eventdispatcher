<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\EventDispatcher;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

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
     * @return array<string, array<class-string, class-string>>
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                EventDispatcherInterface::class    => EventDispatcher::class,
                ListenerSubscriberInterface::class => ListenerSubscriber::class,
            ],
            'factories' => [
                EventDispatcher::class    => Container\EventDispatcherFactory::class,
                ListenerSubscriber::class => Container\ListenerSubscriberFactory::class,
            ],
        ];
    }

    /**
     * @return array<int, array{event: string, listener: class-string, priority?: int}>
     */
    public function getListeners(): array
    {
        return [];
    }

    /**
     * @return array<int, class-string<ListenerSubscriberInterface>>
     */
    public function getSubscribers(): array
    {
        return [];
    }
}
