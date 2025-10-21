<?php

declare(strict_types=1);

namespace Webware\Event;

interface MutableEventInterface extends EventInterface
{
    public function setName(string $name): void;
    public function setTarget(object $target): void;

    /**
     * @param array<array-key, mixed> $params
     */
    public function setParams(array $params): void;
}