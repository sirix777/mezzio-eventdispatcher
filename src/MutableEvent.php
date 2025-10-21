<?php

declare(strict_types=1);

namespace Webware\Event;

class MutableEvent implements EventInterface
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTarget(): ?object
    {
        return $this->target;
    }

    public function setTarget(object $target): void
    {
        $this->target = $target;
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
    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
