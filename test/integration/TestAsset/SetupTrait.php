<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\ConfigProvider as LibConfigProvider;

trait SetupTrait
{
    protected EventDispatcherInterface $eventDispatcher;
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = new ServiceManager();
        $aggregator = new ConfigAggregator([
            LibConfigProvider::class,
            ConfigProvider::class,
        ]);
        $config = $aggregator->getMergedConfig();
        $deps   = $config['dependencies'] ?? [];
        $deps['services']['config'] = $config;
        $this->container = new ServiceManager($deps);

        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
    }
}
