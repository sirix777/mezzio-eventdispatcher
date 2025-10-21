<?php

declare(strict_types=1);

namespace Webware\Event\Container;

use League\Event\EventDispatcher;
use League\Event\EventDispatcherAware;
use Psr\Container\ContainerInterface;

use function assert;

final class EventDispatcherAwareDelegator
{
    public function __invoke(ContainerInterface $container, string $requestedName, callable $callback): mixed
    {
        $serviceInstance = $callback();
        if (! $serviceInstance instanceof EventDispatcherAware) {
            return $serviceInstance;
        }
        $eventDispatcher = $container->get(EventDispatcher::class);
        assert($eventDispatcher instanceof EventDispatcher);
        $serviceInstance->useEventDispatcher($eventDispatcher);
        return $serviceInstance;
    }
}
