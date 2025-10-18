<?php

declare(strict_types=1);

namespace Webware\Event;

class Event implements EventInterface
{
    private readonly string $name;

    public function __construct(
        string $name = self::EVENT_NAME,
        private ?object $target = null,
        private ?array $params  = null,
    ) {
        $this->name = $name !== '' ? $name : static::class;
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
