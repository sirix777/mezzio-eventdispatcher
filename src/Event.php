<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\HasEventName;

class Event implements HasEventName
{
    private readonly string $name;

    public function __construct(
        ?string $name,
        private ?object $target = null,
        private array $params   = [],
    ) {
        $this->name = $name ?? static::class;
    }

    public function eventName(): string
    {
        return $this->name;
    }

    public function getTarget(): ?object
    {
        return $this->target;
    }

    public function setTarget(object $target): void
    {
        $this->target = $target;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
