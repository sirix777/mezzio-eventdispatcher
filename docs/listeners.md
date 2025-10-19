# Listeners

This guide covers creating and managing event listeners in the Mezzio Event Dispatcher library.

## What is a Listener?

A listener is a callable that responds to specific events. When an event is dispatched, all registered listeners for that event are invoked.

## Listener Interface

The simplest way to create a listener is to implement `ListenerInterface`:

```php
namespace Webware\Event;

interface ListenerInterface
{
    public function __invoke(EventInterface $event): void;
}
```

## Creating Basic Listeners

### Invokable Class

```php
use Webware\Event\EventInterface;
use Webware\Event\ListenerInterface;

class WelcomeEmailListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        // Send welcome email
    }
}
```

### With Dependencies

```php
class SendEmailListener implements ListenerInterface
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();

        try {
            $this->emailService->send($user->getEmail(), 'Welcome!');
            $this->logger->info('Email sent', ['user_id' => $user->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Email failed', ['error' => $e->getMessage()]);
        }
    }
}
```

## Registering Listeners

### Container Registration

First, register the listener in your DI container:

```php
// config/autoload/dependencies.global.php
return [
    'dependencies' => [
        'factories' => [
            SendEmailListener::class => SendEmailListenerFactory::class,
        ],
    ],
];
```

### Event Registration

Then register it to listen for events:

```php
// config/autoload/events.global.php
use Webware\Event\ConfigKey;

return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'user.registered',
            'listener' => SendEmailListener::class,
        ],
    ],
];
```

## Listener Patterns

### Single Responsibility

Each listener should handle one specific task:

```php
// Good - Single responsibility
class SendWelcomeEmail implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->emailService->sendWelcome($user);
    }
}

class CreateUserDirectory implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->fileSystem->createUserDirectory($user->getId());
    }
}

// Bad - Multiple responsibilities
class UserSetupListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->emailService->sendWelcome($user);
        $this->fileSystem->createUserDirectory($user->getId());
        $this->analytics->track($user);
        // Too many responsibilities!
    }
}
```

### Type-Safe Listeners

Validate event data:

```php
class OrderProcessListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        // Validate event name
        if ($event->getName() !== 'order.placed') {
            return;
        }

        // Validate target type
        $order = $event->getTarget();
        if (!$order instanceof Order) {
            throw new \InvalidArgumentException('Expected Order instance');
        }

        // Now safely use the order
        $this->processOrder($order);
    }
}
```

### Generic Listeners

Listen to multiple event types:

```php
class AuditLogListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $this->logger->info('Event dispatched', [
            'event_name' => $event->getName(),
            'target_class' => get_class($event->getTarget()),
            'params' => $event->getParams(),
            'timestamp' => time(),
        ]);
    }
}

// Register for multiple events
return [
    ConfigKey::Listeners->value => [
        ['event' => 'user.created', 'listener' => AuditLogListener::class],
        ['event' => 'user.updated', 'listener' => AuditLogListener::class],
        ['event' => 'user.deleted', 'listener' => AuditLogListener::class],
        ['event' => 'order.placed', 'listener' => AuditLogListener::class],
    ],
];
```

## Advanced Listener Patterns

### Conditional Execution

```php
class ConditionalNotificationListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        $params = $event->getParams();

        // Only notify if certain conditions are met
        if (!$this->shouldNotify($user, $params)) {
            return;
        }

        $this->sendNotification($user);
    }

    private function shouldNotify(User $user, array $params): bool
    {
        return $user->hasEmailNotificationsEnabled()
            && ($params['priority'] ?? 'normal') === 'high';
    }
}
```

### Async Processing

```php
class QueuedJobListener implements ListenerInterface
{
    public function __construct(
        private QueueInterface $queue
    ) {}

    public function __invoke(EventInterface $event): void
    {
        // Don't process now, queue for later
        $this->queue->push(new ProcessOrderJob(
            orderId: $event->getTarget()->getId(),
            params: $event->getParams()
        ));
    }
}
```

### Transformation Listeners

```php
class DataEnrichmentListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $entity = $event->getTarget();
        $enrichedData = $this->enrichmentService->enrich($entity);

        // Note: Cannot modify event directly (immutable)
        // Store enriched data in a service or database
        $this->dataStore->save($entity->getId(), $enrichedData);
    }
}
```

### Aggregate Listeners

```php
class NotificationAggregateListener implements ListenerInterface
{
    public function __construct(
        private array $notifiers // EmailNotifier, SmsNotifier, etc.
    ) {}

    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();

        foreach ($this->notifiers as $notifier) {
            if ($notifier->shouldNotify($user)) {
                $notifier->notify($user, $event);
            }
        }
    }
}
```

## Error Handling

### Graceful Degradation

```php
class RobustListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        try {
            $this->doWork($event);
        } catch (\Exception $e) {
            $this->logger->error('Listener failed', [
                'listener' => self::class,
                'event' => $event->getName(),
                'error' => $e->getMessage(),
            ]);

            // Don't throw - let other listeners run
        }
    }
}
```

### Critical Failures

```php
class CriticalListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        try {
            $this->doWork($event);
        } catch (\Exception $e) {
            $this->logger->critical('Critical listener failed', [
                'error' => $e->getMessage(),
            ]);

            // Re-throw for critical operations
            throw $e;
        }
    }
}
```

## Testing Listeners

### Unit Testing

```php
use PHPUnit\Framework\TestCase;

class WelcomeEmailListenerTest extends TestCase
{
    public function testSendsWelcomeEmail(): void
    {
        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())
            ->method('sendWelcome')
            ->with($this->callback(function ($user) {
                return $user->getEmail() === 'test@example.com';
            }));

        $listener = new WelcomeEmailListener($emailService);

        $user = new User(['email' => 'test@example.com']);
        $event = new Event('user.registered', $user);

        $listener($event);
    }
}
```

### Integration Testing

```php
class ListenerIntegrationTest extends TestCase
{
    public function testListenerIsTriggered(): void
    {
        $container = $this->getContainer();
        $dispatcher = $container->get(EventDispatcherInterface::class);

        $user = new User(['email' => 'test@example.com']);
        $event = new Event('user.registered', $user);

        $dispatcher->dispatch($event);

        // Assert side effects
        $this->assertEmailSent('test@example.com');
    }
}
```

## Listener Factories

### Simple Factory

```php
use Psr\Container\ContainerInterface;

class WelcomeEmailListenerFactory
{
    public function __invoke(ContainerInterface $container): WelcomeEmailListener
    {
        return new WelcomeEmailListener(
            $container->get(EmailService::class),
            $container->get(LoggerInterface::class)
        );
    }
}
```

### Factory with Configuration

```php
class ConfigurableListenerFactory
{
    public function __invoke(ContainerInterface $container): ConfigurableListener
    {
        $config = $container->get('config');
        $listenerConfig = $config['listeners']['configurable'] ?? [];

        return new ConfigurableListener(
            $container->get(SomeService::class),
            $listenerConfig
        );
    }
}
```

## Performance Considerations

### Lazy Loading

```php
class LazyListener implements ListenerInterface
{
    private ?ExpensiveService $service = null;

    public function __construct(
        private ContainerInterface $container
    ) {}

    public function __invoke(EventInterface $event): void
    {
        // Only instantiate when needed
        if ($this->service === null) {
            $this->service = $this->container->get(ExpensiveService::class);
        }

        $this->service->process($event);
    }
}
```

### Early Returns

```php
class OptimizedListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        // Early return for unwanted events
        if ($event->getName() !== 'my.event') {
            return;
        }

        $params = $event->getParams();

        // Early return if condition not met
        if (empty($params['process'])) {
            return;
        }

        // Only do expensive work when necessary
        $this->doExpensiveWork($event);
    }
}
```

## Best Practices

1. **Single Responsibility** - One listener, one task
2. **Type Safety** - Validate event data
3. **Error Handling** - Handle exceptions gracefully
4. **Idempotency** - Listeners should be safe to run multiple times
5. **No Side Effects on Event** - Events are immutable
6. **Testability** - Write unit tests for listeners
7. **Documentation** - Document what each listener does

## Common Pitfalls

❌ **Modifying Events**

```php
// Don't do this
public function __invoke(EventInterface $event): void
{
    $event->params['modified'] = true; // Won't work, readonly!
}
```

❌ **Tight Coupling**

```php
// Don't do this
public function __invoke(EventInterface $event): void
{
    $user = $event->getTarget();
    $user->sendEmail(); // Listener shouldn't tell entity what to do
}
```

✅ **Loose Coupling**

```php
// Do this
public function __invoke(EventInterface $event): void
{
    $user = $event->getTarget();
    $this->emailService->sendWelcome($user); // Use a service
}
```

❌ **Ignoring Errors**

```php
// Don't do this
public function __invoke(EventInterface $event): void
{
    try {
        $this->doWork();
    } catch (\Exception $e) {
        // Silent failure
    }
}
```

✅ **Proper Error Handling**

```php
// Do this
public function __invoke(EventInterface $event): void
{
    try {
        $this->doWork();
    } catch (\Exception $e) {
        $this->logger->error('Listener failed', ['error' => $e->getMessage()]);
        // Decide: swallow or re-throw based on criticality
    }
}
```

## Next Steps

- [Subscribers Documentation](subscribers.md) - Group related listeners
- [Priorities Documentation](priorities.md) - Control execution order
- [Testing Documentation](testing.md) - Test your listeners
- [Advanced Usage](advanced-usage.md) - Advanced patterns
