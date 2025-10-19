<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

use Webware\Event\EventInterface;
use Webware\Event\ListenerInterface;

final class PriorityTrackingListener implements ListenerInterface
{
    /** @var array<int, string> */
    public static array $executionOrder = [];

    public function __construct(
        private readonly string $identifier,
    ) {
    }

    public function __invoke(EventInterface $event): void
    {
        self::$executionOrder[] = $this->identifier;
    }

    public static function reset(): void
    {
        self::$executionOrder = [];
    }

    /** @return array<int, string> */
    public static function getExecutionOrder(): array
    {
        return self::$executionOrder;
    }
}
