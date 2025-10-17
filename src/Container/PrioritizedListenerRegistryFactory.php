<?php

declare(strict_types=1);

namespace Axleus\Event\Container;

use League\Event\PrioritizedListenerRegistry;
use Psr\Container\ContainerInterface;

final class PrioritizedListenerRegistryFactory
{
    public function __invoke(ContainerInterface $container): PrioritizedListenerRegistry
    {
        // pull listener config from the container and register them here
        return new PrioritizedListenerRegistry();
    }
}
