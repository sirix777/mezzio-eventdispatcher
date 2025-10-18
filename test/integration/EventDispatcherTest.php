<?php

declare(strict_types=1);

namespace Webware\EventIntegrationTest;

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;
use League\Event\EventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\ConfigProvider as LibConfigProvider;

#[CoversClass(EventDispatcher::class)]
final class EventDispatcherTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = new ServiceManager();
        $aggregator = new ConfigAggregator([
            LibConfigProvider::class,
            TestAsset\ConfigProvider::class,
        ]);
        $config = $aggregator->getMergedConfig();
        $deps   = $config['dependencies'] ?? [];
        $deps['services']['config'] = $config;
        $this->container = new ServiceManager($deps);

        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
    }

    public function testContainerProvidesEventDispatcher(): void
    {
        $this->assertInstanceOf(EventDispatcher::class, $this->eventDispatcher);
    }
}
