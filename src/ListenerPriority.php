<?php

declare(strict_types=1);

namespace Webware\Event;

use League\Event\ListenerPriority as LeagueListenerPriority;

enum ListenerPriority: int
{
    case Low    = LeagueListenerPriority::LOW;
    case Normal = LeagueListenerPriority::NORMAL;
    case High   = LeagueListenerPriority::HIGH;
}
