<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\HasEventName;

interface EventInterface extends HasEventName
{
    public const EVENT_NAME = '';

    public function getTarget(): ?object;
    public function setTarget(object $target): void;
    public function getParams(): ?array;
    public function setParams(array $params): void;

}
