# Advanced Usage

This guide covers advanced patterns, techniques, and architectural considerations for using the Mezzio Event Dispatcher library.

## Advanced Event Patterns

### Event Aggregation

Collect and batch dispatch events:

```php
class EventAggregator
{
    private array $pendingEvents = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function record(EventInterface $event): void
    {
        $this->pendingEvents[] = $event;
    }

    public function flush(): void
    {
        foreach ($this->pendingEvents as $event) {
            $this->dispatcher->dispatch($event);
        }

        $this->pendingEvents = [];
    }

    public function clear(): void
    {
        $this->pendingEvents = [];
    }
}

// Usage
$aggregator->record(new Event('user.created', $user1));
$aggregator->record(new Event('user.created', $user2));
$aggregator->record(new Event('user.created', $user3));

// Dispatch all at once
$aggregator->flush();
```

### Event Versioning

Handle event schema evolution:

```php
class UserCreatedEventV2 extends Event
{
    public const VERSION = 2;

    public function __construct(
        User $user,
        array $metadata = []
    ) {
        parent::__construct('user.created.v2', $user, array_merge(
            $metadata,
            ['version' => self::VERSION]
        ));
    }

    public function getVersion(): int
    {
        return self::VERSION;
    }
}

// Listener handles multiple versions
class UserCreatedListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $version = $event->getParams()['version'] ?? 1;

        match ($version) {
            1 => $this->handleV1($event),
            2 => $this->handleV2($event),
            default => throw new \RuntimeException("Unsupported version: $version"),
        };
    }
}
```

## Advanced Listener Patterns

### Composite Listeners

Combine multiple listeners into one:

```php
class CompositeListener implements ListenerInterface
{
    public function __construct(
        private array $listeners
    ) {}

    public function __invoke(EventInterface $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener($event);
        }
    }
}

// Usage
$composite = new CompositeListener([
    new SendEmailListener($emailService),
    new LogEventListener($logger),
    new UpdateCacheListener($cache),
]);
```

### Filtered Listeners

Listeners that conditionally execute:

```php
class FilteredListener implements ListenerInterface
{
    public function __construct(
        private ListenerInterface $listener,
        private \Closure $filter
    ) {}

    public function __invoke(EventInterface $event): void
    {
        if (($this->filter)($event)) {
            ($this->listener)($event);
        }
    }
}

// Usage
$filtered = new FilteredListener(
    new SendEmailListener($emailService),
    fn($event) => $event->getTarget()->hasEmailNotificationsEnabled()
);
```

### Retry Listeners

Retry failed operations:

```php
class RetryListener implements ListenerInterface
{
    public function __construct(
        private ListenerInterface $listener,
        private int $maxRetries = 3,
        private int $delayMs = 100
    ) {}

    public function __invoke(EventInterface $event): void
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                ($this->listener)($event);
                return;
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $this->maxRetries) {
                    usleep($this->delayMs * 1000);
                }
            }
        }

        throw new \RuntimeException(
            "Failed after {$this->maxRetries} attempts",
            0,
            $lastException
        );
    }
}
```

### Circuit Breaker Pattern

Prevent cascading failures:

```php
class CircuitBreakerListener implements ListenerInterface
{
    private int $failures = 0;
    private bool $open = false;
    private ?int $openedAt = null;

    public function __construct(
        private ListenerInterface $listener,
        private int $threshold = 5,
        private int $timeout = 60
    ) {}

    public function __invoke(EventInterface $event): void
    {
        if ($this->open) {
            if (time() - $this->openedAt > $this->timeout) {
                $this->reset();
            } else {
                throw new \RuntimeException('Circuit breaker is open');
            }
        }

        try {
            ($this->listener)($event);
            $this->onSuccess();
        } catch (\Exception $e) {
            $this->onFailure();
            throw $e;
        }
    }

    private function onSuccess(): void
    {
        $this->failures = 0;
    }

    private function onFailure(): void
    {
        $this->failures++;

        if ($this->failures >= $this->threshold) {
            $this->open = true;
            $this->openedAt = time();
        }
    }

    private function reset(): void
    {
        $this->open = false;
        $this->failures = 0;
        $this->openedAt = null;
    }
}
```

## Event Sourcing Patterns

### Event Store

Persist events for replay:

```php
class EventStore
{
    public function __construct(
        private PDO $db
    ) {}

    public function append(EventInterface $event): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO events (name, target_class, target_id, params, created_at)
             VALUES (:name, :target_class, :target_id, :params, :created_at)'
        );

        $target = $event->getTarget();

        $stmt->execute([
            'name' => $event->getName(),
            'target_class' => $target ? get_class($target) : null,
            'target_id' => $target && method_exists($target, 'getId') ? $target->getId() : null,
            'params' => json_encode($event->getParams()),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function replay(callable $handler): void
    {
        $stmt = $this->db->query('SELECT * FROM events ORDER BY id ASC');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $event = $this->reconstructEvent($row);
            $handler($event);
        }
    }

    private function reconstructEvent(array $row): EventInterface
    {
        // Reconstruct event from stored data
        return new Event(
            $row['name'],
            null, // Would need to reconstruct target
            json_decode($row['params'], true)
        );
    }
}

// Usage with listener
class EventStoreListener implements ListenerInterface
{
    public function __construct(
        private EventStore $store
    ) {}

    public function __invoke(EventInterface $event): void
    {
        $this->store->append($event);
    }
}
```

### CQRS Pattern

Separate read and write models:

```php
// Command events (writes)
class CreateUserCommand extends Event
{
    public function __construct(array $userData)
    {
        parent::__construct('command.user.create', null, $userData);
    }
}

// Query events (reads)
class GetUserQuery extends Event
{
    public function __construct(int $userId)
    {
        parent::__construct('query.user.get', null, ['user_id' => $userId]);
    }
}

// Command handler
class CreateUserCommandHandler implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $data = $event->getParams();
        $user = User::create($data);
        $user->save();

        // Dispatch domain event
        $this->dispatcher->dispatch(new Event('user.created', $user));
    }
}

// Query handler
class GetUserQueryHandler implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $userId = $event->getParams()['user_id'];
        $user = $this->repository->find($userId);

        // Store result somehow (could use event params reference)
    }
}
```

## Saga Pattern

Manage long-running distributed transactions:

```php
class OrderSaga
{
    private array $state = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function start(Order $order): void
    {
        $this->state = ['order_id' => $order->getId(), 'step' => 'payment'];

        try {
            // Step 1: Process payment
            $this->dispatcher->dispatch(new Event('payment.process', $order));
            $this->state['step'] = 'inventory';

            // Step 2: Reserve inventory
            $this->dispatcher->dispatch(new Event('inventory.reserve', $order));
            $this->state['step'] = 'shipping';

            // Step 3: Schedule shipping
            $this->dispatcher->dispatch(new Event('shipping.schedule', $order));
            $this->state['step'] = 'complete';

            // Success
            $this->dispatcher->dispatch(new Event('order.completed', $order));
        } catch (\Exception $e) {
            // Rollback
            $this->compensate($order);
            throw $e;
        }
    }

    private function compensate(Order $order): void
    {
        // Undo completed steps in reverse order
        if ($this->state['step'] === 'shipping') {
            $this->dispatcher->dispatch(new Event('inventory.release', $order));
        }

        if (in_array($this->state['step'], ['inventory', 'shipping'])) {
            $this->dispatcher->dispatch(new Event('payment.refund', $order));
        }
    }
}
```

## Performance Optimization

### Lazy Loading Listeners

```php
class LazyListener implements ListenerInterface
{
    private ?ListenerInterface $listener = null;

    public function __construct(
        private ContainerInterface $container,
        private string $serviceName
    ) {}

    public function __invoke(EventInterface $event): void
    {
        if ($this->listener === null) {
            $this->listener = $this->container->get($this->serviceName);
        }

        ($this->listener)($event);
    }
}
```

### Event Batching

```php
class BatchingListener implements ListenerInterface
{
    private array $batch = [];
    private int $batchSize;

    public function __construct(
        private callable $processor,
        int $batchSize = 100
    ) {
        $this->batchSize = $batchSize;
    }

    public function __invoke(EventInterface $event): void
    {
        $this->batch[] = $event;

        if (count($this->batch) >= $this->batchSize) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if (empty($this->batch)) {
            return;
        }

        ($this->processor)($this->batch);
        $this->batch = [];
    }

    public function __destruct()
    {
        $this->flush();
    }
}
```

### Async Dispatching

```php
class AsyncDispatcher
{
    public function __construct(
        private QueueInterface $queue
    ) {}

    public function dispatch(EventInterface $event): void
    {
        $this->queue->push(new DispatchEventJob(
            eventName: $event->getName(),
            targetClass: $event->getTarget() ? get_class($event->getTarget()) : null,
            targetId: $this->getTargetId($event->getTarget()),
            params: $event->getParams()
        ));
    }

    private function getTargetId(?object $target): mixed
    {
        if ($target === null) {
            return null;
        }

        return method_exists($target, 'getId') ? $target->getId() : null;
    }
}
```

## Middleware Pattern

Add behavior around event dispatching:

```php
interface EventMiddleware
{
    public function process(EventInterface $event, callable $next): void;
}

class LoggingMiddleware implements EventMiddleware
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function process(EventInterface $event, callable $next): void
    {
        $this->logger->info('Before dispatch', ['event' => $event->getName()]);

        $next($event);

        $this->logger->info('After dispatch', ['event' => $event->getName()]);
    }
}

class TimingMiddleware implements EventMiddleware
{
    public function process(EventInterface $event, callable $next): void
    {
        $start = microtime(true);

        $next($event);

        $duration = microtime(true) - $start;
        error_log("Event {$event->getName()} took {$duration}s");
    }
}

class MiddlewareDispatcher
{
    private array $middleware = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function addMiddleware(EventMiddleware $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function dispatch(EventInterface $event): void
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn($next, $middleware) => fn($event) => $middleware->process($event, $next),
            fn($event) => $this->dispatcher->dispatch($event)
        );

        $pipeline($event);
    }
}
```

## Best Practices Summary

1. **Keep Events Immutable** - Never modify event state
2. **Single Responsibility** - Each listener does one thing
3. **Loose Coupling** - Listeners don't know about each other
4. **Error Handling** - Handle exceptions gracefully
5. **Testing** - Comprehensive unit and integration tests
6. **Documentation** - Document event contracts
7. **Monitoring** - Log and monitor event flows
8. **Performance** - Profile and optimize hot paths

## Next Steps

- [API Reference](api-reference.md) - Complete API documentation
- [Testing Guide](testing.md) - Testing strategies
