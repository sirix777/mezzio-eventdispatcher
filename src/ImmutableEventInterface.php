<?php

declare(strict_types=1);

namespace Webware\Event;

interface ImmutableEventInterface extends EventInterface
{
    public function withName(string $name): self;
    public function withTarget(object $target): self;

    /**
     * @param array<array-key, mixed> $params
     */
    public function withParams(array $params): self;
}
