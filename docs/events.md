# Events

This guide provides detailed information about creating and working with events in the Mezzio Event Dispatcher library.

## Event Interfaces

The library provides a flexible event system with three interfaces:

### EventInterface (Base Interface)

All events implement the base `EventInterface`:

```php
namespace Webware\Event;

interface EventInterface extends \League\Event\HasEventName
{
    public function getName(): string;
    public function getTarget(): ?object;
    public function getParams(): ?array;
}
```

### ImmutableEventInterface

For immutable events that return new instances:

```php
namespace Webware\Event;

interface ImmutableEventInterface extends EventInterface
{
    public function withName(string $name): self;
    public function withTarget(object $target): self;
    public function withParams(array $params): self;
}
```

### MutableEventInterface

For mutable events that modify in place:

```php
namespace Webware\Event;

interface MutableEventInterface extends EventInterface
{
    public function setName(string $name): void;
    public function setTarget(object $target): void;
    public function setParams(array $params): void;
}
```

## Choosing Between Immutable and Mutable Events

### ImmutableEvent (Recommended)

Use `ImmutableEvent` for most scenarios:

```php
use Webware\Event\ImmutableEvent;

// Create an event with just a name
$event = new ImmutableEvent('user.login');

// With a target object
$event = new ImmutableEvent('user.login', $user);

// With target and parameters
$event = new ImmutableEvent(
    name: 'user.login',
    target: $user,
    params: [
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'timestamp' => time(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    ]
);
```

**Benefits:**
- Thread-safe and predictable
- Original event state preserved
- Easier debugging and testing
- PSR-14 best practices

### MutableEvent

Use `MutableEvent` when you need to modify events in place:

```php
use Webware\Event\MutableEvent;

$event = new MutableEvent('order.placed', $order);

// Modify the event in place
$event->setName('order.confirmed');
$event->setParams(['payment_method' => 'credit_card']);
```

**Use Cases:**
- Performance-critical scenarios
- Progressive event enrichment by multiple listeners
- Working with legacy code that expects mutable objects
- Event decoration patterns

## Event Properties

### Event Name

The event name identifies the type of event:

```php
$event = new Event('order.placed');

$name = $event->getName(); // 'order.placed'
$name = $event->eventName(); // Same as getName()
```

**Naming Conventions:**

- Use dot notation: `entity.action`
- Be specific: `user.registered` not just `registered`
- Use past tense: `order.created` not `order.create`
- Be consistent across your application

**Examples:**

```php
'user.created'
'user.updated'
'user.deleted'
'order.placed'
'order.shipped'
'order.cancelled'
'payment.processed'
'email.sent'
'cache.cleared'
```

### Event Target

The target is the main subject of the event:

```php
$user = new User(['id' => 1, 'name' => 'John']);
$event = new Event('user.updated', $user);

$target = $event->getTarget(); // Returns $user object
```

Target is optional and can be `null`:

```php
$event = new Event('system.startup');
$target = $event->getTarget(); // null
```

### Event Parameters

Additional context data as an associative array:

```php
$event = new Event(
    'email.sent',
    $email,
    [
        'to' => 'user@example.com',
        'subject' => 'Welcome',
        'success' => true,
    ]
);

$params = $event->getParams(); // Returns array
$to = $params['to']; // 'user@example.com'
```

Parameters default to an empty array:

```php
$event = new Event('simple.event');
$params = $event->getParams(); // []
```

## Working with Immutable Events

`ImmutableEvent` uses methods starting with `with*` that return new instances:

```php
use Webware\Event\ImmutableEvent;

$original = new ImmutableEvent('user.created', $user);

// Create modified versions
$renamed = $original->withName('user.registered');
$withNewTarget = $original->withTarget($newUser);
$withParams = $original->withParams(['source' => 'api']);

// Original is unchanged
echo $original->getName(); // Still 'user.created'
```

### Chaining With Methods

```php
$event = new ImmutableEvent('order.placed', $order);

$enrichedEvent = $event
    ->withName('order.confirmed')
    ->withParams([
        'payment_method' => 'credit_card',
        'total' => $order->getTotal(),
    ]);
```

## Working with Mutable Events

`MutableEvent` uses `set*` methods that modify the event in place:

```php
use Webware\Event\MutableEvent;

$event = new MutableEvent('user.created', $user);

// Modify in place
$event->setName('user.registered');
$event->setTarget($newUser);
$event->setParams(['source' => 'api']);

echo $event->getName(); // 'user.registered'
```

### Sequential Modifications

```php
$event = new MutableEvent('order.placed', $order);

// Each call modifies the same instance
$event->setName('order.confirmed');
$event->setParams(['payment_method' => 'credit_card']);
$event->setParams(array_merge($event->getParams(), [
    'total' => $order->getTotal()
]));
```

## Creating Custom Event Classes

### Custom Immutable Events

For domain-specific immutable events, extend `ImmutableEvent`:

```php
namespace App\Event;

use Webware\Event\ImmutableEvent;
use App\Entity\User;

final readonly class UserRegisteredEvent extends ImmutableEvent
{
    public function __construct(User $user, array $metadata = [])
    {
        parent::__construct(
            name: 'user.registered',
            target: $user,
            params: $metadata
        );
    }

    public function getUser(): User
    {
        $target = $this->getTarget();
        assert($target instanceof User);
        return $target;
    }

    public function getIpAddress(): ?string
    {
        return $this->getParams()['ip_address'] ?? null;
    }

    public function getTimestamp(): int
    {
        return $this->getParams()['timestamp'] ?? time();
    }
}
```

**Usage:**

```php
$event = new UserRegisteredEvent($user, [
    'ip_address' => '192.168.1.1',
    'timestamp' => time(),
]);

$user = $event->getUser(); // Type-hinted User object
$ip = $event->getIpAddress(); // Type-hinted string|null
```

### Custom Mutable Events

For domain-specific mutable events, extend `MutableEvent`:

```php
namespace App\Event;

use Webware\Event\MutableEvent;
use App\Entity\Order;

final class OrderProcessingEvent extends MutableEvent
{
    public function __construct(Order $order)
    {
        parent::__construct(
            name: 'order.processing',
            target: $order,
            params: ['status' => 'pending']
        );
    }

    public function getOrder(): Order
    {
        $target = $this->getTarget();
        assert($target instanceof Order);
        return $target;
    }

    public function getStatus(): string
    {
        return $this->getParams()['status'] ?? 'pending';
    }

    public function updateStatus(string $status): void
    {
        $this->setParams(array_merge($this->getParams(), ['status' => $status]));
    }

    public function addValidationError(string $error): void
    {
        $params = $this->getParams();
        $params['errors'][] = $error;
        $this->setParams($params);
    }
}
```

**Usage:**

```php
$event = new OrderProcessingEvent($order);

// Listeners can progressively enrich the event
$event->updateStatus('validated');
$event->addValidationError('Insufficient stock');
```

**Benefits:**

- Type safety with specific getters
- Domain language
- Encapsulation of event logic
- IDE autocomplete support

## Event Naming Strategies

### Domain-Driven Design

Organize events by bounded context:

```php
// User context
'user.registered'
'user.activated'
'user.passwordChanged'

// Order context
'order.placed'
'order.paid'
'order.shipped'
'order.delivered'

// Inventory context
'inventory.itemAdded'
'inventory.itemRemoved'
'inventory.lowStock'
```

### CRUD Pattern

```php
'entity.created'
'entity.read'    // or entity.viewed
'entity.updated'
'entity.deleted'
```

### Lifecycle Events

```php
'order.created'
'order.validated'
'order.approved'
'order.processing'
'order.completed'
'order.archived'
```

## Event Context Patterns

### Minimal Context

Just the essentials:

```php
$event = new Event('user.loggedIn', $user);
```

### Rich Context

Comprehensive information:

```php
$event = new Event('payment.processed', $payment, [
    'amount' => $payment->getAmount(),
    'currency' => 'USD',
    'method' => 'credit_card',
    'last_four' => '4242',
    'timestamp' => time(),
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'transaction_id' => $transactionId,
]);
```

### Lazy Context

Provide context generators for expensive operations:

```php
class OrderPlacedEvent extends Event
{
    public function __construct(
        private Order $order,
        private \Closure $reportGenerator
    ) {
        parent::__construct('order.placed', $order);
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function generateReport(): Report
    {
        return ($this->reportGenerator)();
    }
}
```

## Stopable Events

While PSR-14 supports stoppable events, this library focuses on notification-style events. For stoppable events, use League Event directly:

```php
use League\Event\StoppableEvent;

class ValidationEvent extends StoppableEvent
{
    private bool $valid = true;

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setInvalid(): void
    {
        $this->valid = false;
        $this->stopPropagation();
    }
}
```

## Working with Events in Listeners

### Type Checking

```php
class UserEventListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        // Check event name
        if ($event->getName() === 'user.created') {
            $this->handleUserCreated($event);
        }

        // Check if target is expected type
        $target = $event->getTarget();
        if ($target instanceof User) {
            $this->processUser($target);
        }
    }
}
```

### Extracting Parameters Safely

```php
public function __invoke(EventInterface $event): void
{
    $params = $event->getParams();

    // With defaults
    $ip = $params['ip_address'] ?? 'unknown';
    $timestamp = $params['timestamp'] ?? time();

    // With validation
    if (isset($params['user_id']) && is_int($params['user_id'])) {
        $userId = $params['user_id'];
    }
}
```

## Event Documentation

Document your events for other developers:

```php
/**
 * Dispatched when a new order is successfully placed.
 *
 * Event Name: order.placed
 *
 * Target: App\Entity\Order - The order that was placed
 *
 * Parameters:
 * - user_id (int): ID of the user who placed the order
 * - total (float): Order total amount
 * - payment_method (string): Payment method used
 * - timestamp (int): Unix timestamp when order was placed
 *
 * Listeners:
 * - SendOrderConfirmation - Sends confirmation email
 * - UpdateInventory - Decrements product stock
 * - NotifyWarehouse - Alerts warehouse of new order
 */
$event = new Event('order.placed', $order, $params);
```

## Best Practices

1. **Immutability** - Never modify event state after creation
2. **Naming Consistency** - Use a consistent naming convention
3. **Type Safety** - Create custom event classes for complex domains
4. **Minimal Coupling** - Events should not know about their listeners
5. **Rich Context** - Provide useful context in parameters
6. **Documentation** - Document event contracts
7. **Past Tense** - Name events for what happened, not what will happen

## Anti-Patterns to Avoid

❌ **Modifying Events**

```php
// Don't do this - events are immutable
$event->name = 'different.name';
```

✅ **Use With Methods**

```php
$newEvent = $event->withName('different.name');
```

❌ **Tight Coupling**

```php
// Don't include listeners in event
$event->addListener($listener);
```

✅ **Register via Configuration**

```php
// Configure in config files
ConfigKey::Listeners->value => [...]
```

❌ **Mutable Target Objects**

```php
// Be careful - target might be modified by listeners
$user = $event->getTarget();
$user->setStatus('modified'); // Other listeners see this change
```

✅ **Defensive Copying**

```php
$user = clone $event->getTarget(); // Work with a copy
```

## Next Steps

- [Listeners Documentation](listeners.md) - Creating event listeners
- [Subscribers Documentation](subscribers.md) - Using subscribers
- [Advanced Usage](advanced-usage.md) - Advanced patterns
