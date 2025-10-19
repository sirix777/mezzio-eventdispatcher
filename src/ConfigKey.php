<?php

declare(strict_types=1);

namespace Webware\Event;

enum ConfigKey: string
{
    case Listeners   = 'listeners';
    case Subscribers = 'subscribers';
}
