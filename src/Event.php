<?php

declare(strict_types=1);

namespace Webware\Event;

readonly class Event implements EventInterface
{
    public function __construct(
        private ?string $name   = self::class,
        private ?object $target = null,
        private ?array $params  = null,
    ) {
    }

    public function getName(): string
    {
        return $this->eventName();
    }

    public function eventName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        return new self($name, $this->target, $this->params);
    }

    public function getTarget(): ?object
    {
        return $this->target;
    }

    public function withTarget(object $target): self
    {
        return new self($this->name, $target, $this->params);
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function withParams(array $params): self
    {
        return new self($this->name, $this->target, $params);
    }
}
