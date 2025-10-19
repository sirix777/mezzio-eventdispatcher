<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\ConfigProvider as LibConfigProvider;

use function assert;
use function is_array;

trait SetupTrait
{
    protected EventDispatcherInterface $eventDispatcher;
    protected ContainerInterface $container;

    protected function setUpEventDispatcher(): void
    {
        $this->container = new ServiceManager();
        $aggregator      = new ConfigAggregator([
            LibConfigProvider::class,
            ConfigProvider::class,
        ]);

        /** @var array<string, mixed> $config */
        $config = $aggregator->getMergedConfig();

        $deps = $config['dependencies'] ?? [];
        assert(is_array($deps));

        if (! isset($deps['services'])) {
            $deps['services'] = [];
        }
        assert(is_array($deps['services']));

        $deps['services']['config'] = $config;

        /** @phpstan-ignore argument.type */
        $this->container = new ServiceManager($deps);

        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
    }
}
