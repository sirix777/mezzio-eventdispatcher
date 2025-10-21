<?php

declare(strict_types=1);

namespace Webware\Event\Container;

use League\Event\EventDispatcherAware;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

use function assert;

final class EventDispatcherAwareDelegator
{
    public function __invoke(ContainerInterface $container, string $requestedName, callable $callback): mixed
    {
        $serviceInstance = $callback();
        if (! $serviceInstance instanceof EventDispatcherAware) {
            return $serviceInstance;
        }
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        assert($eventDispatcher instanceof EventDispatcherInterface);
        $serviceInstance->useEventDispatcher($eventDispatcher);
        return $serviceInstance;
    }
}
