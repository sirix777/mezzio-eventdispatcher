<?php

declare(strict_types=1);

namespace Webware\Event;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use Psr\Container\ContainerInterface;

use function assert;
use function is_array;
use function is_callable;
use function sprintf;

final readonly class ListenerSubscriber implements ListenerSubscriberInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        /** @var array<string, mixed> $config */
        $config = $this->container->get('config');
        /** @var array<int, array{event: string, listener: string|class-string, priority: int}> $listeners */
        $listeners = $config[ConfigKey::Listeners->value] ?? [];

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
            assert(is_callable($listener));

            $registry->subscribeTo(
                $listenerSpec['event'],
                $listener,
                $listenerSpec['priority'] ?? ListenerPriority::Normal->value
            );
        }
    }
}
