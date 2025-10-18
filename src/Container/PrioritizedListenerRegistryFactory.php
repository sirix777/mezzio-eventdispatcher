<?php

declare(strict_types=1);

namespace Webware\Event\Container;

use League\Event\PrioritizedListenerRegistry;
use Psr\Container\ContainerInterface;

final class PrioritizedListenerRegistryFactory
{
    public function __invoke(ContainerInterface $container): PrioritizedListenerRegistry
    {
        // pull listener config from the container and register
        $config = $container->get('config')['listeners'] ?? [];
        $registry = new PrioritizedListenerRegistry();
        foreach ($config as $spec) {
            $listener = $container->has($spec['listener']) ? $container->get($spec['listener']) : null;
            $registry->attach($listener, $spec['priority'] ?? 0);
        }
        return $registry;
    }
}
