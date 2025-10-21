<?php

declare(strict_types=1);

namespace Webware\Event;

use Override;

readonly class ImmutableEvent implements ImmutableEventInterface
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

    #[Override]
    public function getName(): string
    {
        return $this->eventName();
    }

    #[Override]
    public function eventName(): string
    {
        return $this->name ?? self::class;
    }

    #[Override]
    public function withName(string $name): self
    {
        return new self($name, $this->target, $this->params);
    }

    #[Override]
    public function getTarget(): ?object
    {
        return $this->target;
    }

    #[Override]
    public function withTarget(object $target): self
    {
        return new self($this->name, $target, $this->params);
    }

    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function getParams(): array
    {
        return $this->params ?? [];
    }

    /**
     * @param array<array-key, mixed> $params
     */
    #[Override]
    public function withParams(array $params): self
    {
        return new self($this->name, $this->target, $params);
    }
}
