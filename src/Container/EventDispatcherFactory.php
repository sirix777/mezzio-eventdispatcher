<?php

declare(strict_types=1);

namespace Axleus\Event\Container;

use League\Event\EventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatcherFactory
{
    public function __invoke(ContainerInterface $container): EventDispatcherInterface
    {
        return new EventDispatcher();
    }
}
