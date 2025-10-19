<?php

declare(strict_types=1);

namespace Webware\Event;

readonly class Event implements EventInterface
{
    /**
     * @param array<array-key, mixed>|null $params
     */
    public function __construct(
        private ?string $name = self::class,
        private ?object $target = null,
        private ?array $params = null,
    ) {
    }

    public function getName(): string
    {
        return $this->eventName();
    }

    public function eventName(): string
    {
        return $this->name ?? self::class;
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

    /**
     * @return array<array-key, mixed>
     */
    public function getParams(): array
    {
        return $this->params ?? [];
    }

    /**
     * @param array<array-key, mixed> $params
     */
    public function withParams(array $params): self
    {
        return new self($this->name, $this->target, $params);
    }
}
