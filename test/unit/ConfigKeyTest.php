<?php

declare(strict_types=1);

namespace WebwareTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webware\Event\ConfigKey;

#[CoversClass(ConfigKey::class)]
final class ConfigKeyTest extends TestCase
{
    public function testListenersKeyHasCorrectValue(): void
    {
        $this->assertSame('listeners', ConfigKey::Listeners->value);
    }

    public function testSubscribersKeyHasCorrectValue(): void
    {
        $this->assertSame('subscribers', ConfigKey::Subscribers->value);
    }

    public function testEnumHasExactlyTwoCases(): void
    {
        $cases = ConfigKey::cases();
        $this->assertCount(2, $cases);
    }

    public function testEnumCasesAreCorrect(): void
    {
        $cases = ConfigKey::cases();
        $this->assertContains(ConfigKey::Listeners, $cases);
        $this->assertContains(ConfigKey::Subscribers, $cases);
    }

    public function testCanCreateFromValue(): void
    {
        $this->assertSame(ConfigKey::Listeners, ConfigKey::from('listeners'));
        $this->assertSame(ConfigKey::Subscribers, ConfigKey::from('subscribers'));
    }
}
