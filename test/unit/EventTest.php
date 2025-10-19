<?php

declare(strict_types=1);

namespace WebwareTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Webware\Event\Event;
use Webware\Event\EventInterface;

#[CoversClass(Event::class)]
final class EventTest extends TestCase
{
    public function testEventImplementsEventInterface(): void
    {
        $event = new Event();
        $this->assertInstanceOf(EventInterface::class, $event);
    }

    public function testDefaultNameIsClassName(): void
    {
        $event = new Event();
        $this->assertSame(Event::class, $event->getName());
        $this->assertSame(Event::class, $event->eventName());
    }

    public function testCanCreateEventWithCustomName(): void
    {
        $event = new Event('custom.event.name');
        $this->assertSame('custom.event.name', $event->getName());
        $this->assertSame('custom.event.name', $event->eventName());
    }

    public function testCanCreateEventWithTarget(): void
    {
        $target           = new stdClass();
        $target->property = 'value';

        $event = new Event('test.event', $target);

        $this->assertSame($target, $event->getTarget());
        $this->assertSame('value', $event->getTarget()->property);
    }

    public function testCanCreateEventWithParams(): void
    {
        $params = ['key1' => 'value1', 'key2' => 'value2'];
        $event  = new Event('test.event', null, $params);

        $this->assertSame($params, $event->getParams());
    }

    public function testGetParamsReturnsEmptyArrayWhenNotSet(): void
    {
        $event = new Event();
        $this->assertSame([], $event->getParams());
    }

    public function testWithNameCreatesNewInstance(): void
    {
        $event         = new Event('original.name');
        $modifiedEvent = $event->withName('modified.name');

        $this->assertNotSame($event, $modifiedEvent);
        $this->assertSame('original.name', $event->getName());
        $this->assertSame('modified.name', $modifiedEvent->getName());
    }

    public function testWithNamePreservesTarget(): void
    {
        $target        = new stdClass();
        $event         = new Event('test.event', $target);
        $modifiedEvent = $event->withName('new.name');

        $this->assertSame($target, $modifiedEvent->getTarget());
    }

    public function testWithNamePreservesParams(): void
    {
        $params        = ['key' => 'value'];
        $event         = new Event('test.event', null, $params);
        $modifiedEvent = $event->withName('new.name');

        $this->assertSame($params, $modifiedEvent->getParams());
    }

    public function testWithTargetCreatesNewInstance(): void
    {
        $target1 = new stdClass();
        $target2 = new stdClass();

        $event         = new Event('test.event', $target1);
        $modifiedEvent = $event->withTarget($target2);

        $this->assertNotSame($event, $modifiedEvent);
        $this->assertSame($target1, $event->getTarget());
        $this->assertSame($target2, $modifiedEvent->getTarget());
    }

    public function testWithTargetPreservesName(): void
    {
        $target        = new stdClass();
        $event         = new Event('test.event');
        $modifiedEvent = $event->withTarget($target);

        $this->assertSame('test.event', $modifiedEvent->getName());
    }

    public function testWithTargetPreservesParams(): void
    {
        $target        = new stdClass();
        $params        = ['key' => 'value'];
        $event         = new Event('test.event', null, $params);
        $modifiedEvent = $event->withTarget($target);

        $this->assertSame($params, $modifiedEvent->getParams());
    }

    public function testWithParamsCreatesNewInstance(): void
    {
        $params1 = ['key1' => 'value1'];
        $params2 = ['key2' => 'value2'];

        $event         = new Event('test.event', null, $params1);
        $modifiedEvent = $event->withParams($params2);

        $this->assertNotSame($event, $modifiedEvent);
        $this->assertSame($params1, $event->getParams());
        $this->assertSame($params2, $modifiedEvent->getParams());
    }

    public function testWithParamsPreservesName(): void
    {
        $params        = ['key' => 'value'];
        $event         = new Event('test.event');
        $modifiedEvent = $event->withParams($params);

        $this->assertSame('test.event', $modifiedEvent->getName());
    }

    public function testWithParamsPreservesTarget(): void
    {
        $target        = new stdClass();
        $params        = ['key' => 'value'];
        $event         = new Event('test.event', $target);
        $modifiedEvent = $event->withParams($params);

        $this->assertSame($target, $modifiedEvent->getTarget());
    }

    public function testEventIsReadonly(): void
    {
        $event = new Event('test.event');

        // Since it's a readonly class, we can verify by attempting to clone
        $cloned = clone $event;
        $this->assertNotSame($event, $cloned);
        $this->assertSame($event->getName(), $cloned->getName());
    }

    public function testComplexEventWithAllProperties(): void
    {
        $target     = new stdClass();
        $target->id = 123;
        $params     = ['action' => 'create', 'user_id' => 456];

        $event = new Event('user.created', $target, $params);

        $this->assertSame('user.created', $event->getName());
        $this->assertSame($target, $event->getTarget());
        $this->assertSame($params, $event->getParams());
        $this->assertSame(123, $event->getTarget()->id);
    }

    public function testChainingImmutableOperations(): void
    {
        $target1 = new stdClass();
        $target2 = new stdClass();
        $params1 = ['key1' => 'value1'];
        $params2 = ['key2' => 'value2'];

        $original = new Event('original.name', $target1, $params1);
        $modified = $original
            ->withName('modified.name')
            ->withTarget($target2)
            ->withParams($params2);

        // Original unchanged
        $this->assertSame('original.name', $original->getName());
        $this->assertSame($target1, $original->getTarget());
        $this->assertSame($params1, $original->getParams());

        // Modified has all changes
        $this->assertSame('modified.name', $modified->getName());
        $this->assertSame($target2, $modified->getTarget());
        $this->assertSame($params2, $modified->getParams());
    }

    public function testTargetCanBeNull(): void
    {
        $event = new Event('test.event');
        $this->assertNull($event->getTarget());
    }

    public function testParamsCanHandleEmptyArray(): void
    {
        $event = new Event('test.event', null, []);
        $this->assertSame([], $event->getParams());
    }

    public function testParamsCanHandleComplexArrayStructures(): void
    {
        $params = [
            'nested' => [
                'deep' => [
                    'value' => 'test',
                ],
            ],
            'list'   => [1, 2, 3],
            'mixed'  => 'string',
        ];

        $event = new Event('test.event', null, $params);
        $this->assertSame($params, $event->getParams());
    }
}
