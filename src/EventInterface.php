<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\HasEventName;

interface EventInterface extends HasEventName
{
    public function getName(): string;
    public function setName(string $name): void;
    public function getTarget(): ?object;
    public function setTarget(object $target): void;

    /**
     * @return array<array-key, mixed>|null
     */
    public function getParams(): ?array;
    public function setParams(array $params): void;
}
