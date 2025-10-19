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
    public function getParams(): ?array;
    public function withParams(array $params): self;
}
