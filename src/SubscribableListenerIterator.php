<?php

declare(strict_types=1);

namespace Webware\Event;

use FilterIterator;
use Psr\Container\ContainerInterface;

use function is_callable;

final class SubscribableListenerIterator extends FilterIterator
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function accept(): bool
    {
        return $this->getInnerIterator()->current() instanceof ListenerSubscriber;
    }
}
