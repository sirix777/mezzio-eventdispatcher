<?php

declare(strict_types=1);

namespace Webware\Event\Container;

use League\Event\EventDispatcher;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\ConfigKey;

use function assert;

/** @internal */
final class EventDispatcherFactory
{
    public function __invoke(ContainerInterface $container): EventDispatcherInterface
    {
        $listenerSubscriber = $container->get(ListenerSubscriberInterface::class);
        assert($listenerSubscriber instanceof ListenerSubscriberInterface);

        $dispatcher = new EventDispatcher();
        $dispatcher->subscribeListenersFrom($listenerSubscriber);

        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        /** @var class-string<ListenerSubscriberInterface>[] $subscribers */
        $subscribers = $config[ConfigKey::Subscribers->value] ?? [];

        foreach ($subscribers as $subscriber) {
            if ($container->has($subscriber)) {
                $instance = $container->get($subscriber);
                if ($instance instanceof ListenerSubscriberInterface) {
                    $dispatcher->subscribeListenersFrom($instance);
                }
            }
        }

        return $dispatcher;
    }
}
