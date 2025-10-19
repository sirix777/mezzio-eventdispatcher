<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\HasEventName;

interface EventInterface extends HasEventName
{
    public function getName(): string;

    public function withName(string $name): self;

    public function getTarget(): ?object;

    public function withTarget(object $target): self;

    /**
     * @return array<array-key, mixed>|null
     */
    public function getParams(): ?array;

    /**
     * @param array<array-key, mixed> $params
     */
    public function withParams(array $params): self;
}
