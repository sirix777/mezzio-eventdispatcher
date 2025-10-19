<?php

declare(strict_types=1);

namespace WebwareTest\Container;

use League\Event\EventDispatcher;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use stdClass;
use Webware\Event\ConfigKey;
use Webware\Event\Container\EventDispatcherFactory;

#[CoversClass(EventDispatcherFactory::class)]
final class EventDispatcherFactoryTest extends TestCase
{
    private EventDispatcherFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new EventDispatcherFactory();
    }

    public function testFactoryReturnsEventDispatcherInterface(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSubscriber) {
                if ($id === ListenerSubscriberInterface::class) {
                    return $listenerSubscriber;
                }
                return [ConfigKey::Subscribers->value => []];
            });

        $dispatcher = ($this->factory)($container);

        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function testFactoryReturnsEventDispatcher(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSubscriber) {
                if ($id === ListenerSubscriberInterface::class) {
                    return $listenerSubscriber;
                }
                return [ConfigKey::Subscribers->value => []];
            });

        $dispatcher = ($this->factory)($container);

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testFactorySubscribesListenerSubscriber(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);
        $listenerSubscriber->expects($this->once())
            ->method('subscribeListeners');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSubscriber) {
                if ($id === ListenerSubscriberInterface::class) {
                    return $listenerSubscriber;
                }
                return ['config' => [ConfigKey::Subscribers->value => []]];
            });

        ($this->factory)($container);
    }

    public function testFactoryHandlesEmptySubscribersConfig(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSubscriber) {
                if ($id === ListenerSubscriberInterface::class) {
                    return $listenerSubscriber;
                }
                return [ConfigKey::Subscribers->value => []];
            });

        $dispatcher = ($this->factory)($container);

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testFactorySubscribesAdditionalSubscribersFromConfig(): void
    {
        $listenerSubscriber   = $this->createMock(ListenerSubscriberInterface::class);
        $additionalSubscriber = $this->createMock(ListenerSubscriberInterface::class);
        $additionalSubscriber->expects($this->once())
            ->method('subscribeListeners');

        $subscriberClass = 'TestSubscriber';

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                function (string $id) use (
                    $listenerSubscriber,
                    $additionalSubscriber,
                    $subscriberClass
                ) {
                    if ($id === ListenerSubscriberInterface::class) {
                        return $listenerSubscriber;
                    }
                    if ($id === $subscriberClass) {
                        return $additionalSubscriber;
                    }
                    return [ConfigKey::Subscribers->value => [$subscriberClass]];
                }
            );

        $container->method('has')
            ->with($subscriberClass)
            ->willReturn(true);

        ($this->factory)($container);
    }

    public function testFactorySkipsSubscriberNotInContainer(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);
        $subscriberClass    = 'NonExistentSubscriber';

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSubscriber, $subscriberClass) {
                if ($id === ListenerSubscriberInterface::class) {
                    return $listenerSubscriber;
                }
                return [ConfigKey::Subscribers->value => [$subscriberClass]];
            });

        $container->method('has')
            ->with($subscriberClass)
            ->willReturn(false);

        $dispatcher = ($this->factory)($container);

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testFactorySkipsNonListenerSubscriberInstance(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);
        $invalidSubscriber  = new stdClass();
        $subscriberClass    = 'InvalidSubscriber';

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                function (string $id) use (
                    $listenerSubscriber,
                    $invalidSubscriber,
                    $subscriberClass
                ) {
                    if ($id === ListenerSubscriberInterface::class) {
                        return $listenerSubscriber;
                    }
                    if ($id === $subscriberClass) {
                        return $invalidSubscriber;
                    }
                    return [ConfigKey::Subscribers->value => [$subscriberClass]];
                }
            );

        $container->method('has')
            ->with($subscriberClass)
            ->willReturn(true);

        $dispatcher = ($this->factory)($container);

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testFactoryHandlesMissingSubscribersConfigKey(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSubscriber) {
                if ($id === ListenerSubscriberInterface::class) {
                    return $listenerSubscriber;
                }
                return []; // Config without subscribers key
            });

        $dispatcher = ($this->factory)($container);

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testFactorySubscribesMultipleAdditionalSubscribers(): void
    {
        $listenerSubscriber = $this->createMock(ListenerSubscriberInterface::class);
        $subscriber1        = $this->createMock(ListenerSubscriberInterface::class);
        $subscriber2        = $this->createMock(ListenerSubscriberInterface::class);

        $subscriber1->expects($this->once())->method('subscribeListeners');
        $subscriber2->expects($this->once())->method('subscribeListeners');

        $subscriberClass1 = 'TestSubscriber1';
        $subscriberClass2 = 'TestSubscriber2';

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                function (string $id) use (
                    $listenerSubscriber,
                    $subscriber1,
                    $subscriber2,
                    $subscriberClass1,
                    $subscriberClass2
                ) {
                    if ($id === ListenerSubscriberInterface::class) {
                        return $listenerSubscriber;
                    }
                    if ($id === $subscriberClass1) {
                        return $subscriber1;
                    }
                    if ($id === $subscriberClass2) {
                        return $subscriber2;
                    }
                    return [
                        ConfigKey::Subscribers->value => [
                            $subscriberClass1,
                            $subscriberClass2,
                        ],
                    ];
                }
            );

        $container->method('has')
            ->willReturn(true);

        ($this->factory)($container);
    }
}
