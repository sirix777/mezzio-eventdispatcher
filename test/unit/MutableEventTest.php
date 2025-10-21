<?php

declare(strict_types=1);

namespace WebwareTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Webware\Event\EventInterface;
use Webware\Event\MutableEvent;
use Webware\Event\MutableEventInterface;

#[CoversClass(MutableEvent::class)]
final class MutableEventTest extends TestCase
{
    public function testEventImplementsMutableEventInterface(): void
    {
        $event = new MutableEvent('test.event');
        $this->assertInstanceOf(MutableEventInterface::class, $event);
    }

    public function testEventImplementsEventInterface(): void
    {
        $event = new MutableEvent('test.event');
        $this->assertInstanceOf(EventInterface::class, $event);
    }

    public function testConstructorSetsName(): void
    {
        $event = new MutableEvent('test.event');
        $this->assertSame('test.event', $event->getName());
    }

    public function testConstructorAcceptsNullTarget(): void
    {
        $event = new MutableEvent('test.event');
        $this->assertNull($event->getTarget());
    }

    public function testConstructorAcceptsTarget(): void
    {
        $target = new stdClass();
        $event  = new MutableEvent('test.event', $target);
        $this->assertSame($target, $event->getTarget());
    }

    public function testConstructorAcceptsNullParams(): void
    {
        $event = new MutableEvent('test.event');
        $this->assertSame([], $event->getParams());
    }

    public function testConstructorAcceptsParams(): void
    {
        $params = ['key' => 'value'];
        $event  = new MutableEvent('test.event', null, $params);
        $this->assertSame($params, $event->getParams());
    }

    public function testSetNameModifiesSameInstance(): void
    {
        $event = new MutableEvent('test.event');
        $event->setName('new.name');

        // Verify mutation occurred
        $this->assertSame('new.name', $event->getName());
    }

    public function testSetNamePreservesTarget(): void
    {
        $target = new stdClass();
        $event  = new MutableEvent('test.event', $target);
        $event->setName('new.name');

        $this->assertSame($target, $event->getTarget());
    }

    public function testSetNamePreservesParams(): void
    {
        $params = ['key' => 'value'];
        $event  = new MutableEvent('test.event', null, $params);
        $event->setName('new.name');

        $this->assertSame($params, $event->getParams());
    }

    public function testSetTargetModifiesSameInstance(): void
    {
        $target1 = new stdClass();
        $target2 = new stdClass();

        $event = new MutableEvent('test.event', $target1);
        $event->setTarget($target2);

        // Verify mutation occurred
        $this->assertSame($target2, $event->getTarget());
    }

    public function testSetTargetPreservesName(): void
    {
        $target = new stdClass();
        $event  = new MutableEvent('test.event');
        $event->setTarget($target);

        $this->assertSame('test.event', $event->getName());
    }

    public function testSetTargetPreservesParams(): void
    {
        $target = new stdClass();
        $params = ['key' => 'value'];
        $event  = new MutableEvent('test.event', null, $params);
        $event->setTarget($target);

        $this->assertSame($params, $event->getParams());
    }

    public function testSetParamsModifiesSameInstance(): void
    {
        $params1 = ['key1' => 'value1'];
        $params2 = ['key2' => 'value2'];

        $event = new MutableEvent('test.event', null, $params1);
        $event->setParams($params2);

        // Verify mutation occurred
        $this->assertSame($params2, $event->getParams());
    }

    public function testSetParamsPreservesName(): void
    {
        $params = ['key' => 'value'];
        $event  = new MutableEvent('test.event');
        $event->setParams($params);

        $this->assertSame('test.event', $event->getName());
    }

    public function testSetParamsPreservesTarget(): void
    {
        $target = new stdClass();
        $params = ['key' => 'value'];
        $event  = new MutableEvent('test.event', $target);
        $event->setParams($params);

        $this->assertSame($target, $event->getTarget());
    }

    public function testEventIsMutable(): void
    {
        $event = new MutableEvent('test.event');

        // Verify mutation works
        $event->setName('changed.name');
        $this->assertSame('changed.name', $event->getName());

        // Clone should be a different instance
        $cloned = clone $event;
        $this->assertNotSame($event, $cloned);
        $this->assertSame($event->getName(), $cloned->getName());
    }

    public function testComplexEventWithAllProperties(): void
    {
        $target     = new stdClass();
        $target->id = 123;
        $params     = ['action' => 'create', 'user_id' => 456];

        $event = new MutableEvent('user.created', $target, $params);

        $this->assertSame('user.created', $event->getName());
        $this->assertSame($target, $event->getTarget());
        $this->assertSame($params, $event->getParams());
        $this->assertSame(123, $event->getTarget()->id);
    }

    public function testChainingMutableOperations(): void
    {
        $target1 = new stdClass();
        $target2 = new stdClass();
        $params1 = ['key1' => 'value1'];
        $params2 = ['key2' => 'value2'];

        $event = new MutableEvent('original.name', $target1, $params1);
        $event->setName('modified.name');
        $event->setTarget($target2);
        $event->setParams($params2);

        // All properties changed
        $this->assertSame('modified.name', $event->getName());
        $this->assertSame($target2, $event->getTarget());
        $this->assertSame($params2, $event->getParams());
    }

    public function testTargetCanBeNull(): void
    {
        $event = new MutableEvent('test.event');
        $this->assertNull($event->getTarget());
    }

    public function testParamsCanHandleEmptyArray(): void
    {
        $event = new MutableEvent('test.event', null, []);
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

        $event = new MutableEvent('test.event', null, $params);
        $this->assertSame($params, $event->getParams());
    }

    public function testMultipleMutations(): void
    {
        $event = new MutableEvent('test.event');

        // First mutation
        $event->setName('first.change');
        $this->assertSame('first.change', $event->getName());

        // Second mutation
        $event->setName('second.change');
        $this->assertSame('second.change', $event->getName());

        // Third mutation
        $event->setName('third.change');
        $this->assertSame('third.change', $event->getName());
    }

    public function testMutationOfTargetProperties(): void
    {
        $target        = new stdClass();
        $target->value = 'original';

        $event = new MutableEvent('test.event', $target);

        // Modify target object properties
        $target->value = 'modified';

        // Event sees the modification (same object reference)
        $targetFromEvent = $event->getTarget();
        $this->assertNotNull($targetFromEvent);
        $this->assertObjectHasProperty('value', $targetFromEvent);
        /** @phpstan-ignore property.notFound */
        $this->assertSame('modified', $targetFromEvent->value);
    }

    public function testSetParamsToEmptyArray(): void
    {
        $params = ['key' => 'value'];
        $event  = new MutableEvent('test.event', null, $params);

        $this->assertSame($params, $event->getParams());

        // Set to empty array
        $event->setParams([]);
        $this->assertSame([], $event->getParams());
    }

    public function testSetTargetReplacesPreviousTarget(): void
    {
        $target1     = new stdClass();
        $target1->id = 1;
        $target2     = new stdClass();
        $target2->id = 2;

        $event        = new MutableEvent('test.event', $target1);
        $targetBefore = $event->getTarget();
        $this->assertNotNull($targetBefore);
        $this->assertObjectHasProperty('id', $targetBefore);
        /** @phpstan-ignore property.notFound */
        $this->assertSame(1, $targetBefore->id);

        $event->setTarget($target2);
        $targetAfter = $event->getTarget();
        $this->assertNotNull($targetAfter);
        $this->assertObjectHasProperty('id', $targetAfter);
        /** @phpstan-ignore property.notFound */
        $this->assertSame(2, $targetAfter->id);
    }
}
