<?php

declare(strict_types=1);

namespace WebwareTest;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Event\ConfigKey;
use Webware\Event\ListenerPriority;
use Webware\Event\ListenerSubscriber;

#[CoversClass(ListenerSubscriber::class)]
final class ListenerSubscriberTest extends TestCase
{
    public function testImplementsListenerSubscriberInterface(): void
    {
        $container  = $this->createMock(ContainerInterface::class);
        $subscriber = new ListenerSubscriber($container);

        $this->assertInstanceOf(ListenerSubscriberInterface::class, $subscriber);
    }

    public function testSubscribeListenersWithEmptyConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('config')
            ->willReturn([ConfigKey::Listeners->value => []]);

        $registry = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->never())
            ->method('subscribeTo');

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersWithMissingListenersKey(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('config')
            ->willReturn([]);

        $registry = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->never())
            ->method('subscribeTo');

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersRegistersListener(): void
    {
        $listener = function (): void {
        };

        $listenerSpec = [
            'event'    => 'test.event',
            'listener' => 'TestListener',
            'priority' => ListenerPriority::Normal->value,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSpec, $listener) {
                if ($id === 'config') {
                    return [ConfigKey::Listeners->value => [$listenerSpec]];
                }
                return $listener;
            });

        $container->method('has')
            ->with('TestListener')
            ->willReturn(true);

        $registry = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->once())
            ->method('subscribeTo')
            ->with(
                'test.event',
                $listener,
                ListenerPriority::Normal->value
            );

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersUsesDefaultPriorityWhenNotProvided(): void
    {
        $listener = function (): void {
        };

        $listenerSpec = [
            'event'    => 'test.event',
            'listener' => 'TestListener',
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSpec, $listener) {
                if ($id === 'config') {
                    return [ConfigKey::Listeners->value => [$listenerSpec]];
                }
                return $listener;
            });

        $container->method('has')
            ->with('TestListener')
            ->willReturn(true);

        $registry = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->once())
            ->method('subscribeTo')
            ->with(
                'test.event',
                $listener,
                ListenerPriority::Normal->value
            );

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersThrowsExceptionForMissingListener(): void
    {
        $listenerSpec = [
            'event'    => 'test.event',
            'listener' => 'MissingListener',
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('config')
            ->willReturn([ConfigKey::Listeners->value => [$listenerSpec]]);

        $container->method('has')
            ->with('MissingListener')
            ->willReturn(false);

        $registry = $this->createMock(ListenerRegistry::class);

        $subscriber = new ListenerSubscriber($container);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Listener "MissingListener" not found in container');

        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersSkipsInvalidListenerSpec(): void
    {
        $invalidSpecs = [
            'not-an-array',
            ['event' => 'test.event'], // Missing listener key
            [],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('config')
            ->willReturn([ConfigKey::Listeners->value => $invalidSpecs]);

        $registry = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->never())
            ->method('subscribeTo');

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersHandlesMultipleListeners(): void
    {
        $listener1 = function (): void {
        };
        $listener2 = function (): void {
        };

        $listenerSpecs = [
            [
                'event'    => 'event.one',
                'listener' => 'ListenerOne',
                'priority' => ListenerPriority::High->value,
            ],
            [
                'event'    => 'event.two',
                'listener' => 'ListenerTwo',
                'priority' => ListenerPriority::Low->value,
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSpecs, $listener1, $listener2) {
                if ($id === 'config') {
                    return [ConfigKey::Listeners->value => $listenerSpecs];
                }
                if ($id === 'ListenerOne') {
                    return $listener1;
                }
                return $listener2;
            });

        $container->method('has')
            ->willReturn(true);

        $callCount = 0;
        $registry  = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->exactly(2))
            ->method('subscribeTo')
            ->willReturnCallback(function (string $event, callable $listener, int $priority) use (&$callCount): void {
                $callCount++;

                if ($callCount === 1) {
                    $this->assertSame('event.one', $event);
                    $this->assertSame(ListenerPriority::High->value, $priority);
                } elseif ($callCount === 2) {
                    $this->assertSame('event.two', $event);
                    $this->assertSame(ListenerPriority::Low->value, $priority);
                }
            });

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersMixedValidAndInvalidSpecs(): void
    {
        $listener = function (): void {
        };

        $listenerSpecs = [
            'invalid-string',
            [
                'event'    => 'valid.event',
                'listener' => 'ValidListener',
            ],
            ['event' => 'missing.listener'], // Missing listener key
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSpecs, $listener) {
                if ($id === 'config') {
                    return [ConfigKey::Listeners->value => $listenerSpecs];
                }
                return $listener;
            });

        $container->method('has')
            ->with('ValidListener')
            ->willReturn(true);

        $registry = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->once())
            ->method('subscribeTo')
            ->with('valid.event', $listener, ListenerPriority::Normal->value);

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscribeListenersWithCustomPriorities(): void
    {
        $listener = function (): void {
        };

        $listenerSpecs = [
            [
                'event'    => 'high.priority',
                'listener' => 'HighPriorityListener',
                'priority' => ListenerPriority::High->value,
            ],
            [
                'event'    => 'low.priority',
                'listener' => 'LowPriorityListener',
                'priority' => ListenerPriority::Low->value,
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(function (string $id) use ($listenerSpecs, $listener) {
                if ($id === 'config') {
                    return [ConfigKey::Listeners->value => $listenerSpecs];
                }
                return $listener;
            });

        $container->method('has')
            ->willReturn(true);

        $registry = $this->createMock(ListenerRegistry::class);
        $registry->expects($this->exactly(2))
            ->method('subscribeTo');

        $subscriber = new ListenerSubscriber($container);
        $subscriber->subscribeListeners($registry);
    }

    public function testSubscriberIsReadonly(): void
    {
        $container  = $this->createMock(ContainerInterface::class);
        $subscriber = new ListenerSubscriber($container);

        // Since it's a readonly class, we can verify by cloning
        $cloned = clone $subscriber;
        $this->assertNotSame($subscriber, $cloned);
        $this->assertInstanceOf(ListenerSubscriber::class, $cloned);
    }
}
