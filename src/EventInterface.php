<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\HasEventName;

interface EventInterface extends HasEventName
{
    public function getName(): string;

    public function getTarget(): ?object;

    /**
     * @return array<array-key, mixed>|null
     */
    public function getParams(): ?array;
}
