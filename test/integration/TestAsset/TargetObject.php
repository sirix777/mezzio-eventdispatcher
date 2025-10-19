<?php

declare(strict_types=1);

namespace WebwareIntegrationTest\TestAsset;

final class TargetObject
{
    public function __construct(
        public readonly string $data,
    ) {
    }
}
