<?php

declare(strict_types=1);

namespace Webware\Event;

use FilterIterator;
use Iterator;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_callable;
use function sprintf;

final class SubscribableListenerIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly ContainerInterface $container,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        $spec = $this->getInnerIterator()->current();
        if (! is_array($spec) || ! isset($spec['listener'])) {
            return false;
        }

        if (! $this->container->has($spec['listener'])) {
            throw new ServiceNotFoundException(
                sprintf(
                    'Listener service "%s" not found in container',
                    $spec['listener'],
                ),
            );
        }

        $listener = $this->container->get($spec['listener']);
        return is_callable($listener);
    }
}
