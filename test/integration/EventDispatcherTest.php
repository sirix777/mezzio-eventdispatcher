<?php

declare(strict_types=1);

namespace WebwareIntegrationTest;

use League\Event\EventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\EventInterface;

#[CoversClass(EventDispatcher::class)]
final class EventDispatcherTest extends TestCase
{
    use TestAsset\SetupTrait;

    protected function setUp(): void
    {
        $this->setUpEventDispatcher();
        TestAsset\PriorityTrackingListener::reset();
    }

    public function testContainerProvidesEventDispatcher(): void
    {
        $this->assertInstanceOf(EventDispatcher::class, $this->eventDispatcher);
    }

    public function testContainerProvidesEventDispatcherInterface(): void
    {
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $this->assertSame($this->eventDispatcher, $dispatcher);
    }

    public function testEventDispatcherInvokesListeners(): void
    {
        $event  = new TestAsset\Event('some.event.name');
        $result = $this->eventDispatcher->dispatch($event);

        $this->assertInstanceOf(TestAsset\Event::class, $result);

        $listener = $this->container->get(TestAsset\ListenerOne::class);
        $this->assertInstanceOf(TestAsset\ListenerOne::class, $listener);
        $this->assertTrue($listener->wasInvoked());
        $this->assertSame(1, $listener->getInvocationCount());
    }

    public function testEventDispatcherInvokesMultipleListeners(): void
    {
        $event  = new TestAsset\Event('multiple.listeners.event');
        $result = $this->eventDispatcher->dispatch($event);

        $this->assertInstanceOf(TestAsset\Event::class, $result);

        $listenerOne   = $this->container->get(TestAsset\ListenerOne::class);
        $listenerTwo   = $this->container->get(TestAsset\ListenerTwo::class);
        $listenerThree = $this->container->get(TestAsset\ListenerThree::class);

        $this->assertInstanceOf(TestAsset\ListenerOne::class, $listenerOne);
        $this->assertInstanceOf(TestAsset\ListenerTwo::class, $listenerTwo);
        $this->assertInstanceOf(TestAsset\ListenerThree::class, $listenerThree);

        $this->assertTrue($listenerOne->wasInvoked());
        $this->assertTrue($listenerTwo->wasInvoked());
        $this->assertTrue($listenerThree->wasInvoked());

        // Each listener should have been invoked once for this event
        // Note: ListenerOne is also invoked by 'some.event.name' in another test
        $this->assertContains('multiple.listeners.event', $listenerOne->invocations);
        $this->assertContains('multiple.listeners.event', $listenerTwo->invocations);
        $this->assertContains('multiple.listeners.event', $listenerThree->invocations);
    }

    public function testListenerPriorityIsRespected(): void
    {
        $event = new TestAsset\Event('priority.test.event');
        $this->eventDispatcher->dispatch($event);

        $executionOrder = TestAsset\PriorityTrackingListener::getExecutionOrder();

        // High priority listeners execute first
        $this->assertSame(['high', 'normal', 'low'], $executionOrder);
    }

    public function testEventSubscribersAreRegistered(): void
    {
        $event = new TestAsset\Event('subscriber.event');
        $this->eventDispatcher->dispatch($event);

        $subscriber = $this->container->get(TestAsset\TestSubscriber::class);
        $this->assertInstanceOf(TestAsset\TestSubscriber::class, $subscriber);
        $this->assertTrue($subscriber->wasInvoked());
        $this->assertContains('subscriber.event', $subscriber->getInvocations());
    }

    public function testMultipleEventsCanBeHandledBySubscriber(): void
    {
        $event1 = new TestAsset\Event('subscriber.event');
        $event2 = new TestAsset\Event('another.subscriber.event');

        $this->eventDispatcher->dispatch($event1);
        $this->eventDispatcher->dispatch($event2);

        $subscriber = $this->container->get(TestAsset\TestSubscriber::class);
        $this->assertInstanceOf(TestAsset\TestSubscriber::class, $subscriber);
        $this->assertTrue($subscriber->wasInvoked());

        $invocations = $subscriber->getInvocations();
        $this->assertCount(2, $invocations);
        $this->assertContains('subscriber.event', $invocations);
        $this->assertContains('another.subscriber.event', $invocations);
    }

    public function testEventWithTargetObject(): void
    {
        $target          = new TestAsset\TargetObject('test-data');
        $event           = new TestAsset\Event('some.event.name');
        $eventWithTarget = $event->withTarget($target);

        $result = $this->eventDispatcher->dispatch($eventWithTarget);

        $this->assertNotSame($event, $eventWithTarget);
        $this->assertSame($target, $eventWithTarget->getTarget());
        $this->assertInstanceOf(EventInterface::class, $result);
        $this->assertSame($target, $result->getTarget());
        $this->assertInstanceOf(TestAsset\TargetObject::class, $result->getTarget());
        $this->assertSame('test-data', $result->getTarget()->data);
    }

    public function testEventWithParams(): void
    {
        $params          = ['key1' => 'value1', 'key2' => 'value2'];
        $event           = new TestAsset\Event('some.event.name');
        $eventWithParams = $event->withParams($params);

        $result = $this->eventDispatcher->dispatch($eventWithParams);

        $this->assertNotSame($event, $eventWithParams);
        $this->assertSame($params, $eventWithParams->getParams());
        $this->assertInstanceOf(EventInterface::class, $result);
        $this->assertSame($params, $result->getParams());
    }

    public function testEventImmutabilityWithName(): void
    {
        $event         = new TestAsset\Event('original.name');
        $modifiedEvent = $event->withName('modified.name');

        $this->assertNotSame($event, $modifiedEvent);
        $this->assertSame('original.name', $event->getName());
        $this->assertSame('modified.name', $modifiedEvent->getName());
    }

    public function testEventImmutabilityWithTarget(): void
    {
        $target1 = new TestAsset\TargetObject('target1');
        $target2 = new TestAsset\TargetObject('target2');

        $event         = new TestAsset\Event('test.event', $target1);
        $modifiedEvent = $event->withTarget($target2);

        $this->assertNotSame($event, $modifiedEvent);
        $this->assertSame($target1, $event->getTarget());
        $this->assertSame($target2, $modifiedEvent->getTarget());
    }

    public function testEventImmutabilityWithParams(): void
    {
        $params1 = ['key' => 'value1'];
        $params2 = ['key' => 'value2'];

        $event         = new TestAsset\Event('test.event', null, $params1);
        $modifiedEvent = $event->withParams($params2);

        $this->assertNotSame($event, $modifiedEvent);
        $this->assertSame($params1, $event->getParams());
        $this->assertSame($params2, $modifiedEvent->getParams());
    }

    public function testComplexEventWithAllProperties(): void
    {
        $target = new TestAsset\TargetObject('complex-data');
        $params = ['user_id' => 123, 'action' => 'create'];

        $event = new TestAsset\Event(
            name: 'user.created',
            target: $target,
            params: $params
        );

        $this->assertSame('user.created', $event->getName());
        $this->assertSame($target, $event->getTarget());
        $this->assertSame($params, $event->getParams());

        // Test chaining immutable operations
        $modifiedEvent = $event
            ->withName('user.updated')
            ->withParams(['user_id' => 456, 'action' => 'update']);

        $this->assertSame('user.created', $event->getName());
        $this->assertSame('user.updated', $modifiedEvent->getName());
        $this->assertSame($target, $modifiedEvent->getTarget());
        $this->assertSame(['user_id' => 456, 'action' => 'update'], $modifiedEvent->getParams());
    }

    public function testEventsWithoutListenersAreHandledGracefully(): void
    {
        $event  = new TestAsset\Event('unregistered.event');
        $result = $this->eventDispatcher->dispatch($event);

        $this->assertInstanceOf(TestAsset\Event::class, $result);
        $this->assertSame('unregistered.event', $result->getName());
    }

    public function testEventNameMatchingIsExact(): void
    {
        // Dispatch an event that is similar but not exact
        $event = new TestAsset\Event('some.event.name.extra');
        $this->eventDispatcher->dispatch($event);

        $listener = $this->container->get(TestAsset\ListenerOne::class);
        $this->assertInstanceOf(TestAsset\ListenerOne::class, $listener);

        // The listener should NOT be invoked for non-exact matches
        $this->assertNotContains('some.event.name.extra', $listener->invocations);
    }
}
