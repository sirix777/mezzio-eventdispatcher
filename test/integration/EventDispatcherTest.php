<?php

declare(strict_types=1);

namespace WebwareIntegrationTest;

use League\Event\EventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventDispatcher::class)]
final class EventDispatcherTest extends TestCase
{
    use TestAsset\SetupTrait;

    public function testContainerProvidesEventDispatcher(): void
    {
        $this->assertInstanceOf(EventDispatcher::class, $this->eventDispatcher);
    }

    public function testEventDispatcherInvokesListeners(): void
    {
        $event   = new TestAsset\Event('some.event.name');
        $results = $this->eventDispatcher->dispatch($event);

        $this->assertInstanceOf(TestAsset\Event::class, $results);
    }
}
