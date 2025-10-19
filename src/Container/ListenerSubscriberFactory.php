<?php

declare(strict_types=1);

namespace Webware\Event\Container;

use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use Psr\Container\ContainerInterface;
use Webware\Event\ListenerSubscriber;

final class ListenerSubscriberFactory
{
    public function __invoke(ContainerInterface $container): ListenerSubscriberInterface
    {
        return new ListenerSubscriber($container);
    }
}
