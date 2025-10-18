<?php

declare(strict_types=1);

namespace Webware\Event\Container;

use ArrayIterator;
use League\Event\PrioritizedListenerRegistry;
use Psr\Container\ContainerInterface;
use Webware\Event\ListenerPriority;
use Webware\Event\SubscribableListenerIterator;

final class PrioritizedListenerRegistryFactory
{
    public function __invoke(ContainerInterface $container): PrioritizedListenerRegistry
    {
        // pull listener config from the container and register
        $listeners = $container->get('config')['listeners'] ?? [];
        $registry  = new PrioritizedListenerRegistry();
        $iterator  = new SubscribableListenerIterator(
            new ArrayIterator($listeners),
            $container,
        );
        foreach ($iterator as $listenerSpec) {
            $listener = $container->get($listenerSpec['listener']);
            $registry->subscribeTo(
                $listenerSpec['event'],
                $listener,
                $listenerSpec['priority'] ?? ListenerPriority::Normal->value
            );
        }
        return $registry;
    }
}
