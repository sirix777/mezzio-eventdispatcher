# Priorities

This guide explains how listener priorities work and how to control the execution order of your event listeners.

## Understanding Priorities

When multiple listeners are registered for the same event, priorities determine the order in which they execute.

### Priority Rules

- **Higher numbers = Higher priority = Earlier execution**
- Default priority is `0` (Normal)
- Priorities can be any integer value
- Listeners with the same priority execute in registration order

## ListenerPriority Enum

The library provides a `ListenerPriority` enum with standard priority levels:

```php
use Webware\Event\ListenerPriority;

ListenerPriority::High->value;   // 100 - Runs first
ListenerPriority::Normal->value; // 0   - Default
ListenerPriority::Low->value;    // -100 - Runs last
```

These values come from `League\Event\ListenerPriority`.

## Using Priorities

### In Configuration

```php
use Webware\Event\ConfigKey;
use Webware\Event\ListenerPriority;

return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'order.placed',
            'listener' => ValidateOrderListener::class,
            'priority' => ListenerPriority::High->value, // 100 - First
        ],
        [
            'event' => 'order.placed',
            'listener' => ProcessOrderListener::class,
            'priority' => ListenerPriority::Normal->value, // 0 - Second
        ],
        [
            'event' => 'order.placed',
            'listener' => SendEmailListener::class,
            'priority' => ListenerPriority::Low->value, // -100 - Third
        ],
    ],
];
```

### In Subscribers

```php
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;
use Webware\Event\ListenerPriority;

class OrderSubscriber implements ListenerSubscriber
{
    public function subscribeListeners(ListenerRegistry $registry): void
    {
        $registry->subscribeTo(
            'order.placed',
            [$this, 'validate'],
            ListenerPriority::High->value // Runs first
        );

        $registry->subscribeTo(
            'order.placed',
            [$this, 'process'],
            ListenerPriority::Normal->value // Runs second
        );

        $registry->subscribeTo(
            'order.placed',
            [$this, 'notify'],
            ListenerPriority::Low->value // Runs last
        );
    }
}
```

## Common Priority Patterns

### Validation → Processing → Notification

```php
return [
    ConfigKey::Listeners->value => [
        // 1. Validate first
        [
            'event' => 'user.register',
            'listener' => ValidateUserDataListener::class,
            'priority' => 200, // Very high
        ],

        // 2. Create user account
        [
            'event' => 'user.register',
            'listener' => CreateUserListener::class,
            'priority' => 100, // High
        ],

        // 3. Set up user resources
        [
            'event' => 'user.register',
            'listener' => SetupUserResourcesListener::class,
            'priority' => 0, // Normal
        ],

        // 4. Send welcome email last
        [
            'event' => 'user.register',
            'listener' => SendWelcomeEmailListener::class,
            'priority' => -100, // Low
        ],
    ],
];
```

### Critical → Important → Optional

```php
return [
    ConfigKey::Listeners->value => [
        // Critical: Must run first
        [
            'event' => 'payment.received',
            'listener' => RecordPaymentListener::class,
            'priority' => ListenerPriority::High->value,
        ],

        // Important: Should run
        [
            'event' => 'payment.received',
            'listener' => UpdateOrderStatusListener::class,
            'priority' => ListenerPriority::Normal->value,
        ],

        // Optional: Nice to have
        [
            'event' => 'payment.received',
            'listener' => SendAnalyticsListener::class,
            'priority' => ListenerPriority::Low->value,
        ],
    ],
];
```

### Setup → Core → Cleanup

```php
return [
    ConfigKey::Listeners->value => [
        // Setup
        [
            'event' => 'app.request',
            'listener' => InitializeSessionListener::class,
            'priority' => 100,
        ],

        // Core processing
        [
            'event' => 'app.request',
            'listener' => AuthenticateUserListener::class,
            'priority' => 0,
        ],

        // Cleanup
        [
            'event' => 'app.request',
            'listener' => LogRequestListener::class,
            'priority' => -100,
        ],
    ],
];
```

## Custom Priority Values

You can use any integer value for fine-grained control:

```php
return [
    ConfigKey::Listeners->value => [
        ['event' => 'process', 'listener' => First::class, 'priority' => 1000],
        ['event' => 'process', 'listener' => Second::class, 'priority' => 500],
        ['event' => 'process', 'listener' => Third::class, 'priority' => 100],
        ['event' => 'process', 'listener' => Fourth::class, 'priority' => 50],
        ['event' => 'process', 'listener' => Fifth::class, 'priority' => 0],
        ['event' => 'process', 'listener' => Sixth::class, 'priority' => -50],
        ['event' => 'process', 'listener' => Seventh::class, 'priority' => -100],
        ['event' => 'process', 'listener' => Eighth::class, 'priority' => -500],
        ['event' => 'process', 'listener' => Last::class, 'priority' => -1000],
    ],
];
```

## Priority Best Practices

### 1. Use Standard Priorities

Prefer `ListenerPriority` enum values for consistency:

```php
// Good
'priority' => ListenerPriority::High->value,

// Avoid unless you have a specific reason
'priority' => 73,
```

### 2. Document Why

Explain non-obvious priority choices:

```php
[
    'event' => 'user.login',
    'listener' => CheckBannedUsersListener::class,
    // High priority: Must check ban status before processing login
    'priority' => ListenerPriority::High->value,
],
```

### 3. Reserve Extreme Values

Keep very high/low values for critical scenarios:

```php
// Reserve for absolutely critical operations
'priority' => 1000,  // or 10000

// Reserve for final cleanup
'priority' => -1000, // or -10000
```

### 4. Group Related Priorities

Keep related listeners near each other:

```php
return [
    ConfigKey::Listeners->value => [
        // Database operations (100-199)
        ['event' => 'app.startup', 'listener' => ConnectDB::class, 'priority' => 150],
        ['event' => 'app.startup', 'listener' => MigrateDB::class, 'priority' => 100],

        // Cache operations (0-99)
        ['event' => 'app.startup', 'listener' => WarmCache::class, 'priority' => 50],
        ['event' => 'app.startup', 'listener' => ClearOldCache::class, 'priority' => 0],

        // Logging operations (-100 to -1)
        ['event' => 'app.startup', 'listener' => LogStartup::class, 'priority' => -50],
    ],
];
```

## Common Use Cases

### Data Validation

Validate before processing:

```php
[
    'event' => 'data.save',
    'listener' => ValidateDataListener::class,
    'priority' => ListenerPriority::High->value, // Validate first
],
[
    'event' => 'data.save',
    'listener' => SaveDataListener::class,
    'priority' => ListenerPriority::Normal->value, // Then save
],
```

### Database Transactions

Order database operations correctly:

```php
[
    'event' => 'order.complete',
    'listener' => BeginTransactionListener::class,
    'priority' => 200, // Start transaction first
],
[
    'event' => 'order.complete',
    'listener' => SaveOrderListener::class,
    'priority' => 100, // Save data
],
[
    'event' => 'order.complete',
    'listener' => CommitTransactionListener::class,
    'priority' => -200, // Commit last
],
```

### Logging and Debugging

Log after main processing:

```php
[
    'event' => 'api.request',
    'listener' => ProcessRequestListener::class,
    'priority' => ListenerPriority::Normal->value,
],
[
    'event' => 'api.request',
    'listener' => LogRequestListener::class,
    'priority' => ListenerPriority::Low->value, // Log after processing
],
```

### Cache Warming

Warm cache after data changes:

```php
[
    'event' => 'product.updated',
    'listener' => UpdateProductListener::class,
    'priority' => ListenerPriority::High->value, // Update first
],
[
    'event' => 'product.updated',
    'listener' => InvalidateCacheListener::class,
    'priority' => ListenerPriority::Normal->value, // Clear cache
],
[
    'event' => 'product.updated',
    'listener' => WarmCacheListener::class,
    'priority' => ListenerPriority::Low->value, // Warm cache last
],
```

## Testing Priority Order

Verify listeners execute in the correct order:

```php
class PriorityTest extends TestCase
{
    public function testListenersExecuteInPriorityOrder(): void
    {
        $executionOrder = [];

        $high = function ($event) use (&$executionOrder) {
            $executionOrder[] = 'high';
        };

        $normal = function ($event) use (&$executionOrder) {
            $executionOrder[] = 'normal';
        };

        $low = function ($event) use (&$executionOrder) {
            $executionOrder[] = 'low';
        };

        $dispatcher = new EventDispatcher();
        $dispatcher->subscribeTo('test', $high, ListenerPriority::High->value);
        $dispatcher->subscribeTo('test', $normal, ListenerPriority::Normal->value);
        $dispatcher->subscribeTo('test', $low, ListenerPriority::Low->value);

        $dispatcher->dispatch(new Event('test'));

        $this->assertSame(['high', 'normal', 'low'], $executionOrder);
    }
}
```

## Debugging Priority Issues

### Log Execution Order

```php
class DebugListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        error_log(sprintf(
            'Listener %s executing for event %s',
            static::class,
            $event->getName()
        ));

        // Your logic here
    }
}
```

### Visualize Listener Order

```php
// In development, list all listeners for an event
$container = $this->getContainer();
$config = $container->get('config');
$listeners = $config[ConfigKey::Listeners->value];

// Filter by event
$orderListeners = array_filter($listeners, fn($l) => $l['event'] === 'order.placed');

// Sort by priority
usort($orderListeners, fn($a, $b) =>
    ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0)
);

// Display
foreach ($orderListeners as $listener) {
    echo sprintf(
        "Priority %d: %s\n",
        $listener['priority'] ?? 0,
        $listener['listener']
    );
}
```

## Common Mistakes

❌ **Assuming Registration Order**

```php
// Don't rely on this order without priorities
return [
    ConfigKey::Listeners->value => [
        ['event' => 'test', 'listener' => First::class],
        ['event' => 'test', 'listener' => Second::class],
        ['event' => 'test', 'listener' => Third::class],
    ],
];
```

✅ **Be Explicit with Priorities**

```php
// Do this - be explicit
return [
    ConfigKey::Listeners->value => [
        ['event' => 'test', 'listener' => First::class, 'priority' => 300],
        ['event' => 'test', 'listener' => Second::class, 'priority' => 200],
        ['event' => 'test', 'listener' => Third::class, 'priority' => 100],
    ],
];
```

❌ **Reversed Priority Logic**

```php
// Wrong - thinking lower numbers run first
['listener' => Important::class, 'priority' => 1],
['listener' => LessImportant::class, 'priority' => 100],
```

✅ **Correct Priority Logic**

```php
// Right - higher numbers run first
['listener' => Important::class, 'priority' => 100],
['listener' => LessImportant::class, 'priority' => 1],
```

## Next Steps

- [Advanced Usage](advanced-usage.md) - Advanced patterns
- [Testing Documentation](testing.md) - Testing strategies
- [API Reference](api-reference.md) - Complete API documentation
