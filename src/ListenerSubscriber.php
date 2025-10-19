<?php

declare(strict_types=1);

namespace Webware\Event;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function sprintf;

final readonly class ListenerSubscriber implements ListenerSubscriberInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        $listeners = $this->container->get('config')[ConfigKey::Listeners->value] ?? [];
        foreach ($listeners as $listenerSpec) {
            if (! is_array($listenerSpec) || ! isset($listenerSpec['listener'])) {
                continue;
            }
            if (! $this->container->has($listenerSpec['listener'])) {
                throw new ServiceNotFoundException(
                    sprintf(
                        'Listener "%s" not found in container',
                        $listenerSpec['listener'],
                    ),
                );
            }
            $listener = $this->container->get($listenerSpec['listener']);
            $registry->subscribeTo(
                $listenerSpec['event'],
                $listener,
                $listenerSpec['priority'] ?? ListenerPriority::Normal->value
            );
        }
    }
}
