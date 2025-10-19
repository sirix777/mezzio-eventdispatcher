# Subscribers

Subscribers provide a way to register multiple listeners from a single class, making it easier to organize related event listeners together.

## What is a Subscriber?

A subscriber is a class that implements `League\Event\ListenerSubscriber` and can subscribe to multiple events at once. Instead of registering individual listeners in configuration, you register the subscriber class.

## Creating a Subscriber

### Basic Subscriber

```php
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;
use Webware\Event\ListenerPriority;

class UserEventSubscriber implements ListenerSubscriber
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {}

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        // Subscribe to user.created event
        $registry->subscribeTo(
            'user.created',
            [$this, 'onUserCreated'],
            ListenerPriority::Normal->value
        );

        // Subscribe to user.updated event
        $registry->subscribeTo(
            'user.updated',
            [$this, 'onUserUpdated'],
            ListenerPriority::Normal->value
        );

        // Subscribe to user.deleted event
        $registry->subscribeTo(
            'user.deleted',
            [$this, 'onUserDeleted'],
            ListenerPriority::Low->value
        );
    }

    public function onUserCreated(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->emailService->sendWelcome($user);
        $this->logger->info('User created', ['user_id' => $user->getId()]);
    }

    public function onUserUpdated(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->logger->info('User updated', ['user_id' => $user->getId()]);
    }

    public function onUserDeleted(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->logger->info('User deleted', ['user_id' => $user->getId()]);
    }
}
```

## Registering Subscribers

### Container Registration

First, register the subscriber as a service:

```php
// config/autoload/dependencies.global.php
return [
    'dependencies' => [
        'factories' => [
            UserEventSubscriber::class => UserEventSubscriberFactory::class,
        ],
    ],
];
```

### Event Registration

Then register it in the subscribers configuration:

```php
// config/autoload/events.global.php
use Webware\Event\ConfigKey;

return [
    ConfigKey::Subscribers->value => [
        UserEventSubscriber::class,
        OrderEventSubscriber::class,
        PaymentEventSubscriber::class,
    ],
];
```

## Subscriber Factory

Create a factory for dependency injection:

```php
use Psr\Container\ContainerInterface;

class UserEventSubscriberFactory
{
    public function __invoke(ContainerInterface $container): UserEventSubscriber
    {
        return new UserEventSubscriber(
            $container->get(EmailService::class),
            $container->get(LoggerInterface::class)
        );
    }
}
```

## Subscriber Patterns

### Domain-Driven Subscribers

Group listeners by domain:

```php
class OrderEventSubscriber implements ListenerSubscriber
{
    public function __construct(
        private OrderService $orderService,
        private InventoryService $inventoryService,
        private EmailService $emailService
    ) {}

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        $registry->subscribeTo('order.placed', [$this, 'onOrderPlaced']);
        $registry->subscribeTo('order.paid', [$this, 'onOrderPaid']);
        $registry->subscribeTo('order.shipped', [$this, 'onOrderShipped']);
        $registry->subscribeTo('order.delivered', [$this, 'onOrderDelivered']);
        $registry->subscribeTo('order.cancelled', [$this, 'onOrderCancelled']);
    }

    public function onOrderPlaced(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->inventoryService->reserve($order);
        $this->emailService->sendOrderConfirmation($order);
    }

    public function onOrderPaid(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->orderService->markPaid($order);
    }

    public function onOrderShipped(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->emailService->sendShippingNotification($order);
    }

    public function onOrderDelivered(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->orderService->markDelivered($order);
        $this->inventoryService->confirmDelivery($order);
    }

    public function onOrderCancelled(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->inventoryService->release($order);
        $this->emailService->sendCancellationNotification($order);
    }
}
```

### Cross-Cutting Concerns

Subscribers for system-wide concerns:

```php
class LoggingSubscriber implements ListenerSubscriber
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        // Log all important events
        $events = [
            'user.created', 'user.updated', 'user.deleted',
            'order.placed', 'order.cancelled',
            'payment.processed', 'payment.failed',
        ];

        foreach ($events as $event) {
            $registry->subscribeTo($event, [$this, 'logEvent']);
        }
    }

    public function logEvent(EventInterface $event): void
    {
        $this->logger->info($event->getName(), [
            'target' => $this->getTargetInfo($event->getTarget()),
            'params' => $event->getParams(),
        ]);
    }

    private function getTargetInfo(?object $target): array
    {
        if ($target === null) {
            return [];
        }

        return [
            'class' => get_class($target),
            'id' => method_exists($target, 'getId') ? $target->getId() : null,
        ];
    }
}
```

### Analytics Subscribers

```php
class AnalyticsSubscriber implements ListenerSubscriber
{
    public function __construct(
        private AnalyticsService $analytics
    ) {}

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        $registry->subscribeTo('user.registered', [$this, 'trackUserRegistration']);
        $registry->subscribeTo('order.placed', [$this, 'trackOrderPlacement']);
        $registry->subscribeTo('payment.processed', [$this, 'trackPayment']);
    }

    public function trackUserRegistration(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->analytics->track('User Registration', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'source' => $event->getParams()['source'] ?? 'unknown',
        ]);
    }

    public function trackOrderPlacement(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->analytics->track('Order Placed', [
            'order_id' => $order->getId(),
            'total' => $order->getTotal(),
            'items' => count($order->getItems()),
        ]);
    }

    public function trackPayment(EventInterface $event): void
    {
        $payment = $event->getTarget();
        $this->analytics->track('Payment Processed', [
            'payment_id' => $payment->getId(),
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod(),
        ]);
    }
}
```

## Advanced Patterns

### Conditional Subscriptions

```php
class ConditionalSubscriber implements ListenerSubscriber
{
    public function __construct(
        private bool $featureEnabled,
        private NotificationService $notifications
    ) {}

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        // Only subscribe if feature is enabled
        if ($this->featureEnabled) {
            $registry->subscribeTo('user.action', [$this, 'sendNotification']);
        }
    }

    public function sendNotification(EventInterface $event): void
    {
        $this->notifications->send($event);
    }
}
```

### Priority Management

```php
class PrioritizedSubscriber implements ListenerSubscriber
{
    public function subscribeListeners(ListenerRegistry $registry): void
    {
        // High priority - run first
        $registry->subscribeTo(
            'order.placed',
            [$this, 'validateOrder'],
            ListenerPriority::High->value
        );

        // Normal priority
        $registry->subscribeTo(
            'order.placed',
            [$this, 'processOrder'],
            ListenerPriority::Normal->value
        );

        // Low priority - run last
        $registry->subscribeTo(
            'order.placed',
            [$this, 'sendNotifications'],
            ListenerPriority::Low->value
        );
    }

    public function validateOrder(EventInterface $event): void
    {
        // Validation logic
    }

    public function processOrder(EventInterface $event): void
    {
        // Processing logic
    }

    public function sendNotifications(EventInterface $event): void
    {
        // Notification logic
    }
}
```

### Generic Event Handlers

```php
class GenericEventSubscriber implements ListenerSubscriber
{
    private array $handlers = [];

    public function __construct(/* dependencies */)
    {
        $this->handlers = [
            'user.created' => [$this, 'handleUserCreated'],
            'user.updated' => [$this, 'handleUserUpdated'],
            'order.placed' => [$this, 'handleOrderPlaced'],
        ];
    }

    public function subscribeListeners(ListenerRegistry $registry): void
    {
        foreach ($this->handlers as $event => $handler) {
            $registry->subscribeTo($event, $handler);
        }
    }

    public function handleUserCreated(EventInterface $event): void { /* ... */ }
    public function handleUserUpdated(EventInterface $event): void { /* ... */ }
    public function handleOrderPlaced(EventInterface $event): void { /* ... */ }
}
```

## Testing Subscribers

### Unit Testing

```php
class UserEventSubscriberTest extends TestCase
{
    public function testSubscribesToCorrectEvents(): void
    {
        $registry = $this->createMock(ListenerRegistry::class);

        $registry->expects($this->exactly(3))
            ->method('subscribeTo')
            ->withConsecutive(
                ['user.created', $this->anything(), $this->anything()],
                ['user.updated', $this->anything(), $this->anything()],
                ['user.deleted', $this->anything(), $this->anything()]
            );

        $subscriber = new UserEventSubscriber(
            $this->createMock(EmailService::class),
            $this->createMock(LoggerInterface::class)
        );

        $subscriber->subscribeListeners($registry);
    }

    public function testOnUserCreatedSendsEmail(): void
    {
        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())
            ->method('sendWelcome');

        $subscriber = new UserEventSubscriber(
            $emailService,
            $this->createMock(LoggerInterface::class)
        );

        $user = new User(['id' => 1]);
        $event = new Event('user.created', $user);

        $subscriber->onUserCreated($event);
    }
}
```

### Integration Testing

```php
class SubscriberIntegrationTest extends TestCase
{
    public function testSubscriberIsRegistered(): void
    {
        $container = $this->getContainer();
        $subscriber = $container->get(UserEventSubscriber::class);

        $this->assertInstanceOf(UserEventSubscriber::class, $subscriber);
    }

    public function testSubscriberHandlesEvents(): void
    {
        $container = $this->getContainer();
        $dispatcher = $container->get(EventDispatcherInterface::class);

        $user = new User(['id' => 1]);
        $event = new Event('user.created', $user);

        $dispatcher->dispatch($event);

        // Assert side effects
        $this->assertEmailSent($user->getEmail());
    }
}
```

## Subscribers vs Individual Listeners

### Use Subscribers When

- ✅ Multiple related listeners share dependencies
- ✅ You want to organize listeners by domain
- ✅ You need complex subscription logic
- ✅ Listeners are tightly coupled to each other

### Use Individual Listeners When

- ✅ Listeners are simple and independent
- ✅ Maximum flexibility in configuration
- ✅ Listeners might be reused across different contexts
- ✅ You prefer configuration-based registration

## Best Practices

1. **Domain Organization** - Group related events in one subscriber
2. **Single Responsibility** - Each method handles one event
3. **Dependency Injection** - Use constructor injection
4. **Error Handling** - Handle exceptions in each method
5. **Testing** - Test subscriber registration and handlers separately
6. **Documentation** - Document which events are handled

## Common Pitfalls

❌ **Too Many Events**

```php
// Don't do this
class MegaSubscriber implements ListenerSubscriber
{
    public function subscribeListeners(ListenerRegistry $registry): void
    {
        // Subscribing to 50+ different events
        // This is too much for one class!
    }
}
```

✅ **Focused Subscribers**

```php
// Do this
class UserSubscriber implements ListenerSubscriber { /* User events */ }
class OrderSubscriber implements ListenerSubscriber { /* Order events */ }
class PaymentSubscriber implements ListenerSubscriber { /* Payment events */ }
```

❌ **Tight Coupling**

```php
// Don't do this
public function onOrderPlaced(EventInterface $event): void
{
    $order = $event->getTarget();
    $order->sendEmail(); // Entity shouldn't send emails
    $order->updateInventory(); // Entity shouldn't update inventory
}
```

✅ **Loose Coupling**

```php
// Do this
public function onOrderPlaced(EventInterface $event): void
{
    $order = $event->getTarget();
    $this->emailService->sendOrderConfirmation($order);
    $this->inventoryService->updateStock($order);
}
```

## Next Steps

- [Priorities Documentation](priorities.md) - Managing execution order
- [Advanced Usage](advanced-usage.md) - Advanced patterns
- [Testing Documentation](testing.md) - Testing strategies
