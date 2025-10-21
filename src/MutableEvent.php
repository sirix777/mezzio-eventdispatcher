<?php

declare(strict_types=1);

namespace Webware\Event;

use Override;

class MutableEvent implements MutableEventInterface
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
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    #[Override]
    public function getTarget(): ?object
    {
        return $this->target;
    }

    #[Override]
    public function setTarget(object $target): void
    {
        $this->target = $target;
    }

    #[Override]
    public function getParams(): array
    {
        return $this->params ?? [];
    }

    #[Override]
    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
