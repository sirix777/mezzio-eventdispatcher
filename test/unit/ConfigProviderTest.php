<?php

declare(strict_types=1);

namespace WebwareTest;

use League\Event\EventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\ConfigKey;
use Webware\Event\ConfigProvider;
use Webware\Event\Container\EventDispatcherFactory;
use Webware\Event\Container\ListenerSubscriberFactory;
use Webware\Event\ListenerSubscriber;

use function class_exists;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->configProvider = new ConfigProvider();
    }

    public function testInvokeReturnsCompleteConfiguration(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey(ConfigKey::Listeners->value, $config);
        $this->assertArrayHasKey(ConfigKey::Subscribers->value, $config);
    }

    public function testInvokeDependenciesContainsAliases(): void
    {
        $config = ($this->configProvider)();

        $this->assertIsArray($config['dependencies']);
        $this->assertArrayHasKey('aliases', $config['dependencies']);
        $this->assertIsArray($config['dependencies']['aliases']);
    }

    public function testInvokeDependenciesContainsFactories(): void
    {
        $config = ($this->configProvider)();

        $this->assertIsArray($config['dependencies']);
        $this->assertArrayHasKey('factories', $config['dependencies']);
        $this->assertIsArray($config['dependencies']['factories']);
    }

    public function testGetDependenciesReturnsCorrectStructure(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertArrayHasKey('aliases', $dependencies);
        $this->assertArrayHasKey('factories', $dependencies);
    }

    public function testGetDependenciesAliasesIncludesPsrEventDispatcher(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertArrayHasKey(EventDispatcherInterface::class, $dependencies['aliases']);
        $this->assertSame(
            EventDispatcher::class,
            $dependencies['aliases'][EventDispatcherInterface::class]
        );
    }

    public function testGetDependenciesAliasesIncludesListenerSubscriber(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertArrayHasKey(\League\Event\ListenerSubscriber::class, $dependencies['aliases']);
        $this->assertSame(
            ListenerSubscriber::class,
            $dependencies['aliases'][\League\Event\ListenerSubscriber::class]
        );
    }

    public function testGetDependenciesFactoriesIncludesEventDispatcher(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertArrayHasKey(EventDispatcher::class, $dependencies['factories']);
        $this->assertSame(
            EventDispatcherFactory::class,
            $dependencies['factories'][EventDispatcher::class]
        );
    }

    public function testGetDependenciesFactoriesIncludesListenerSubscriber(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertArrayHasKey(ListenerSubscriber::class, $dependencies['factories']);
        $this->assertSame(
            ListenerSubscriberFactory::class,
            $dependencies['factories'][ListenerSubscriber::class]
        );
    }

    public function testGetListenersReturnsEmptyArray(): void
    {
        $listeners = $this->configProvider->getListeners();

        $this->assertEmpty($listeners);
    }

    public function testGetSubscribersReturnsEmptyArray(): void
    {
        $subscribers = $this->configProvider->getSubscribers();

        $this->assertEmpty($subscribers);
    }

    public function testInvokeListenersKeyUsesConfigKeyEnum(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey(ConfigKey::Listeners->value, $config);
        $this->assertSame('listeners', ConfigKey::Listeners->value);
    }

    public function testInvokeSubscribersKeyUsesConfigKeyEnum(): void
    {
        $config = ($this->configProvider)();

        $this->assertArrayHasKey(ConfigKey::Subscribers->value, $config);
        $this->assertSame('subscribers', ConfigKey::Subscribers->value);
    }

    public function testConfigurationMatchesMergedPublicMethods(): void
    {
        $config = ($this->configProvider)();

        $this->assertSame($this->configProvider->getDependencies(), $config['dependencies']);
        $this->assertSame($this->configProvider->getListeners(), $config[ConfigKey::Listeners->value]);
        $this->assertSame($this->configProvider->getSubscribers(), $config[ConfigKey::Subscribers->value]);
    }

    public function testDependenciesHasTwoAliases(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertCount(2, $dependencies['aliases']);
    }

    public function testDependenciesHasTwoFactories(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $this->assertCount(2, $dependencies['factories']);
    }

    public function testConfigProviderCanBeInstantiatedMultipleTimes(): void
    {
        $provider1 = new ConfigProvider();
        $provider2 = new ConfigProvider();

        $config1 = ($provider1)();
        $config2 = ($provider2)();

        $this->assertEquals($config1, $config2);
    }

    public function testAllAliasesPointToValidClasses(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        $expectedAliases = [
            EventDispatcherInterface::class         => EventDispatcher::class,
            \League\Event\ListenerSubscriber::class => ListenerSubscriber::class,
        ];

        foreach ($expectedAliases as $alias => $target) {
            $this->assertArrayHasKey($alias, $dependencies['aliases']);
            $this->assertSame($target, $dependencies['aliases'][$alias]);
        }
    }

    public function testAllFactoriesAreClassStrings(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        foreach ($dependencies['factories'] as $service => $factory) {
            $this->assertIsString($factory);
            $this->assertTrue(class_exists($factory), "Factory class {$factory} does not exist");
        }
    }
}
