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
use function is_int;
use function is_string;
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
        $config    = $this->container->get('config');
        $listeners = $config[ConfigKey::Listeners->value] ?? [];
        assert(is_array($listeners));

        foreach ($listeners as $listenerSpec) {
            if (! is_array($listenerSpec) || ! isset($listenerSpec['listener'])) {
                continue;
            }

            $listenerId = $listenerSpec['listener'];
            assert(is_string($listenerId));

            if (! $this->container->has($listenerId)) {
                throw new ServiceNotFoundException(
                    sprintf(
                        'Listener "%s" not found in container',
                        $listenerId,
                    ),
                );
            }

            $listener = $this->container->get($listenerId);
            assert(is_callable($listener));

            $event = $listenerSpec['event'];
            assert(is_string($event));

            $priority = $listenerSpec['priority'] ?? ListenerPriority::Normal->value;
            assert(is_int($priority));

            $registry->subscribeTo(
                $event,
                $listener,
                $priority
            );
        }
    }
}
