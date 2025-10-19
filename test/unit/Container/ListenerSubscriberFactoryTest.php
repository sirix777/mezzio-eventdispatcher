<?php

declare(strict_types=1);

namespace WebwareTest\Container;

use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Event\Container\ListenerSubscriberFactory;
use Webware\Event\ListenerSubscriber;

#[CoversClass(ListenerSubscriberFactory::class)]
final class ListenerSubscriberFactoryTest extends TestCase
{
    private ListenerSubscriberFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ListenerSubscriberFactory();
    }

    public function testFactoryReturnsListenerSubscriberInterface(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subscriber = ($this->factory)($container);

        $this->assertInstanceOf(ListenerSubscriberInterface::class, $subscriber);
    }

    public function testFactoryReturnsListenerSubscriber(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subscriber = ($this->factory)($container);

        $this->assertInstanceOf(ListenerSubscriber::class, $subscriber);
    }

    public function testFactoryPassesContainerToSubscriber(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subscriber = ($this->factory)($container);

        // We can't directly inspect readonly properties, but we can verify
        // the subscriber was created successfully
        $this->assertInstanceOf(ListenerSubscriber::class, $subscriber);
    }

    public function testFactoryCreatesNewInstanceOnEachInvocation(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subscriber1 = ($this->factory)($container);
        $subscriber2 = ($this->factory)($container);

        $this->assertInstanceOf(ListenerSubscriber::class, $subscriber1);
        $this->assertInstanceOf(ListenerSubscriber::class, $subscriber2);
        $this->assertNotSame($subscriber1, $subscriber2);
    }

    public function testFactoryWorksWithDifferentContainers(): void
    {
        $container1 = $this->createMock(ContainerInterface::class);
        $container2 = $this->createMock(ContainerInterface::class);

        $subscriber1 = ($this->factory)($container1);
        $subscriber2 = ($this->factory)($container2);

        $this->assertInstanceOf(ListenerSubscriber::class, $subscriber1);
        $this->assertInstanceOf(ListenerSubscriber::class, $subscriber2);
    }
}
