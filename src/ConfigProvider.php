<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\EventDispatcher;
use League\Event\ListenerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                EventDispatcherInterface::class => EventDispatcher::class,
                ListenerRegistryInterface::class => ListenerRegistry::class,
            ],
            'factories' => [
                EventDispatcher::class => Container\EventDispatcherFactory::class,
                ListenerRegistry::class => Container\PrioritizedListenerRegistryFactory::class,
            ],
        ];
    }
}
