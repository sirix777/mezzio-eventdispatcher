<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\EventDispatcher;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'                => $this->getDependencies(),
            ConfigKey::Listeners->value   => $this->getListeners(),
            ConfigKey::Subscribers->value => $this->getSubscribers(),
        ];
    }

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

    public function getListeners(): array
    {
        return [];
    }

    public function getSubscribers(): array
    {
        return [];
    }
}
