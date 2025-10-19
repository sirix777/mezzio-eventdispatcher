<?php

declare(strict_types=1);

namespace Webware\Event;

interface ListenerInterface
{
    public function __invoke(EventInterface $event): void;
}
