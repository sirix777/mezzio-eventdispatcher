<?php

declare(strict_types=1);

namespace Webware\Event\Container;

use Webware\Event\ListenerRegistryInterface;
use League\Event\EventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatcherFactory
{
    public function __invoke(ContainerInterface $container): EventDispatcherInterface
    {
        $registry = $container->get(ListenerRegistryInterface::class);
        return new EventDispatcher($registry);
    }
}
