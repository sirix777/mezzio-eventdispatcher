<?php

declare(strict_types=1);

namespace WebwareTest;

use League\Event\ListenerPriority as LeagueListenerPriority;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webware\Event\ListenerPriority;

#[CoversClass(ListenerPriority::class)]
final class ListenerPriorityTest extends TestCase
{
    public function testLowPriorityHasCorrectValue(): void
    {
        $this->assertSame(LeagueListenerPriority::LOW, ListenerPriority::Low->value);
    }

    public function testNormalPriorityHasCorrectValue(): void
    {
        $this->assertSame(LeagueListenerPriority::NORMAL, ListenerPriority::Normal->value);
    }

    public function testHighPriorityHasCorrectValue(): void
    {
        $this->assertSame(LeagueListenerPriority::HIGH, ListenerPriority::High->value);
    }

    public function testEnumHasExactlyThreeCases(): void
    {
        $cases = ListenerPriority::cases();
        $this->assertCount(3, $cases);
    }

    public function testEnumCasesAreCorrect(): void
    {
        $cases = ListenerPriority::cases();
        $this->assertContains(ListenerPriority::Low, $cases);
        $this->assertContains(ListenerPriority::Normal, $cases);
        $this->assertContains(ListenerPriority::High, $cases);
    }

    public function testCanCreateFromValue(): void
    {
        $this->assertSame(ListenerPriority::Low, ListenerPriority::from(LeagueListenerPriority::LOW));
        $this->assertSame(ListenerPriority::Normal, ListenerPriority::from(LeagueListenerPriority::NORMAL));
        $this->assertSame(ListenerPriority::High, ListenerPriority::from(LeagueListenerPriority::HIGH));
    }

    public function testPriorityValuesAreInCorrectOrder(): void
    {
        // In League Event, higher numeric values = higher priority (executed first)
        // HIGH (100) > NORMAL (0) > LOW (-100)
        $this->assertGreaterThan(ListenerPriority::Normal->value, ListenerPriority::High->value);
        $this->assertGreaterThan(ListenerPriority::Low->value, ListenerPriority::Normal->value);
        $this->assertGreaterThan(ListenerPriority::Low->value, ListenerPriority::High->value);
    }
}
