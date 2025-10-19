# Configuration

This guide covers all configuration options for the Mezzio Event Dispatcher library.

## Configuration Structure

The library uses two main configuration keys:

```php
use Webware\Event\ConfigKey;

return [
    ConfigKey::Listeners->value => [
        // Listener configurations
    ],
    ConfigKey::Subscribers->value => [
        // Subscriber class names
    ],
];
```

## Listener Configuration

Each listener configuration is an array with the following structure:

```php
[
    'event' => 'event.name',           // Required: Event name to listen for
    'listener' => ListenerClass::class, // Required: Service ID of listener
    'priority' => 0,                    // Optional: Execution priority (default: 0)
]
```

### Basic Listener Configuration

```php
use Webware\Event\ConfigKey;
use Webware\Event\ListenerPriority;

return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'user.created',
            'listener' => UserCreatedListener::class,
            'priority' => ListenerPriority::Normal->value,
        ],
    ],
];
```

### Multiple Listeners for Same Event

```php
return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'order.placed',
            'listener' => SendOrderConfirmation::class,
            'priority' => ListenerPriority::Normal->value,
        ],
        [
            'event' => 'order.placed',
            'listener' => UpdateInventory::class,
            'priority' => ListenerPriority::High->value, // Runs first
        ],
        [
            'event' => 'order.placed',
            'listener' => NotifyWarehouse::class,
            'priority' => ListenerPriority::Low->value, // Runs last
        ],
    ],
];
```

### Multiple Events per Listener

A listener can listen to multiple events:

```php
return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'user.created',
            'listener' => AuditLogger::class,
        ],
        [
            'event' => 'user.updated',
            'listener' => AuditLogger::class,
        ],
        [
            'event' => 'user.deleted',
            'listener' => AuditLogger::class,
        ],
    ],
];
```

## Subscriber Configuration

Subscribers are classes that can subscribe to multiple events at once:

```php
use Webware\Event\ConfigKey;

return [
    ConfigKey::Subscribers->value => [
        UserEventSubscriber::class,
        OrderEventSubscriber::class,
    ],
];
```

Subscriber classes must implement `League\Event\ListenerSubscriber` and be registered in the container.

See [Subscribers Documentation](subscribers.md) for detailed subscriber usage.

## Priority Configuration

### Using Priority Enum

The library provides a `ListenerPriority` enum for common priority levels:

```php
use Webware\Event\ListenerPriority;

// Available priorities (from League Event):
ListenerPriority::High->value;   // 100 - Runs first
ListenerPriority::Normal->value; // 0   - Default
ListenerPriority::Low->value;    // -100 - Runs last
```

### Custom Priorities

You can use custom integer values:

```php
return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'app.startup',
            'listener' => CriticalInitializer::class,
            'priority' => 1000, // Very high priority
        ],
        [
            'event' => 'app.startup',
            'listener' => NormalSetup::class,
            'priority' => 0,
        ],
        [
            'event' => 'app.startup',
            'listener' => OptionalCleanup::class,
            'priority' => -1000, // Very low priority
        ],
    ],
];
```

### **Higher numbers = Higher priority = Earlier execution**

## Organizing Configuration

### Single Configuration File

For small projects:

```php
// config/autoload/events.global.php
use Webware\Event\ConfigKey;

return [
    ConfigKey::Listeners->value => [
        // All listeners
    ],
    ConfigKey::Subscribers->value => [
        // All subscribers
    ],
];
```

### Module-Based Configuration

For larger projects, organize by module:

```php
// config/autoload/events.global.php
use Webware\Event\ConfigKey;

return [
    ConfigKey::Listeners->value => array_merge(
        require __DIR__ . '/events/user.php',
        require __DIR__ . '/events/order.php',
        require __DIR__ . '/events/payment.php',
    ),
];

// config/autoload/events/user.php
return [
    [
        'event' => 'user.created',
        'listener' => UserCreatedListener::class,
    ],
    // More user listeners...
];
```

### ConfigProvider Pattern

Using separate ConfigProviders for each module:

```php
// src/User/ConfigProvider.php
namespace App\User;

use Webware\Event\ConfigKey;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            ConfigKey::Listeners->value => [
                [
                    'event' => 'user.created',
                    'listener' => UserCreatedListener::class,
                ],
            ],
            'dependencies' => [
                'factories' => [
                    UserCreatedListener::class => UserCreatedListenerFactory::class,
                ],
            ],
        ];
    }
}

// config/config.php
$aggregator = new ConfigAggregator([
    \Webware\Event\ConfigProvider::class,
    \App\User\ConfigProvider::class,
    \App\Order\ConfigProvider::class,
]);
```

## Environment-Specific Configuration

### Development vs Production

```php
// config/autoload/events.global.php
use Webware\Event\ConfigKey;

$listeners = [
    [
        'event' => 'error.occurred',
        'listener' => LogErrorListener::class,
    ],
];

// Add debug listeners in development
if (getenv('APP_ENV') === 'development') {
    $listeners[] = [
        'event' => 'error.occurred',
        'listener' => DisplayErrorListener::class,
    ];
}

return [
    ConfigKey::Listeners->value => $listeners,
];
```

### Local Configuration Overrides

```php
// config/autoload/events.local.php
// This file is in .gitignore for local overrides
use Webware\Event\ConfigKey;

return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'email.send',
            'listener' => LogEmailInsteadOfSending::class, // Development only
        ],
    ],
];
```

## Dependency Configuration

Don't forget to register your listeners in the dependency injection container:

```php
// config/autoload/dependencies.global.php
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'dependencies' => [
        'factories' => [
            // Simple listeners with no dependencies
            SimpleListener::class => InvokableFactory::class,

            // Listeners with dependencies
            ComplexListener::class => ComplexListenerFactory::class,

            // Using ReflectionBasedAbstractFactory for auto-wiring
            // Note: Requires laminas-servicemanager configuration
        ],

        'abstract_factories' => [
            \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
    ],
];
```

## Configuration Validation

The library performs validation:

- **Missing listener**: Throws `ServiceNotFoundException` if listener not in container
- **Invalid spec**: Skips configurations without 'listener' key
- **Invalid priority**: Validates priority is an integer

## Complete Configuration Example

```php
// config/autoload/events.global.php
use Webware\Event\ConfigKey;
use Webware\Event\ListenerPriority;

return [
    // Listeners
    ConfigKey::Listeners->value => [
        // User events
        [
            'event' => 'user.registered',
            'listener' => SendWelcomeEmail::class,
            'priority' => ListenerPriority::Normal->value,
        ],
        [
            'event' => 'user.registered',
            'listener' => CreateUserDirectory::class,
            'priority' => ListenerPriority::High->value,
        ],

        // Order events
        [
            'event' => 'order.placed',
            'listener' => SendOrderConfirmation::class,
        ],
        [
            'event' => 'order.shipped',
            'listener' => SendShippingNotification::class,
        ],

        // System events
        [
            'event' => 'app.error',
            'listener' => LogErrorListener::class,
            'priority' => ListenerPriority::High->value,
        ],
    ],

    // Subscribers
    ConfigKey::Subscribers->value => [
        UserEventSubscriber::class,
        OrderEventSubscriber::class,
    ],

    // Dependencies
    'dependencies' => [
        'factories' => [
            SendWelcomeEmail::class => SendWelcomeEmailFactory::class,
            CreateUserDirectory::class => CreateUserDirectoryFactory::class,
            SendOrderConfirmation::class => InvokableFactory::class,
            SendShippingNotification::class => InvokableFactory::class,
            LogErrorListener::class => LogErrorListenerFactory::class,
            UserEventSubscriber::class => UserEventSubscriberFactory::class,
            OrderEventSubscriber::class => OrderEventSubscriberFactory::class,
        ],
    ],
];
```

## Best Practices

1. **Use ConfigKey Enum** - Always use `ConfigKey::Listeners->value` for consistency
2. **Use Priority Enum** - Use `ListenerPriority` enum for standard priorities
3. **Organize by Domain** - Group related listeners together
4. **Document Priorities** - Comment why specific priorities are used
5. **Validate in Development** - Test configuration loading during development
6. **Keep It Simple** - Don't over-complicate with too many priorities

## Next Steps

- [Events Documentation](events.md) - Creating custom events
- [Listeners Documentation](listeners.md) - Advanced listener patterns
- [Subscribers Documentation](subscribers.md) - Using subscribers
- [Priorities](priorities.md) - Understanding execution order
