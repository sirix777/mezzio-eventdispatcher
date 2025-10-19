# Mezzio Event Dispatcher

[![PHP Version](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-green)](LICENSE)

A PSR-14 Event Dispatcher library for Mezzio/Laminas applications, providing seamless integration between [League Event](https://event.thephpleague.com/) and Laminas ServiceManager.

## Features

- ✅ **PSR-14 Compliant** - Full PSR-14 Event Dispatcher implementation
- ✅ **Laminas Integration** - Native ServiceManager/Dependency Injection support
- ✅ **Configuration-Based** - Register listeners and subscribers via configuration arrays
- ✅ **Immutable Events** - Type-safe, immutable event objects with fluent API
- ✅ **Priority Support** - Control listener execution order with priority levels
- ✅ **Type Safe** - PHP 8.2+ with strict types, readonly classes, and comprehensive PHPStan Level 10 coverage
- ✅ **Fully Tested** - 91 tests with comprehensive unit and integration coverage

## Requirements

- PHP 8.2, 8.3, 8.4, or 8.5
- [league/event](https://packagist.org/packages/league/event) ^3.0
- PSR-11 Container implementation (e.g., Laminas ServiceManager)

## Installation

```bash
composer require webware/mezzio-eventdispatcher
```

## Quick Start

### 1. Register the ConfigProvider

Add the configuration provider to your application:

```php
// config/config.php
use Webware\Event\ConfigProvider;

$aggregator = new ConfigAggregator([
    ConfigProvider::class,
    // ... other config providers
]);

return $aggregator->getMergedConfig();
```

### 2. Create an Event

```php
use Webware\Event\Event;

$event = new Event('user.created', $user, ['timestamp' => time()]);
```

### 3. Create a Listener

```php
use Webware\Event\EventInterface;
use Webware\Event\ListenerInterface;

class UserCreatedListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        $params = $event->getParams();

        // Handle the event...
    }
}
```

### 4. Register Listener in Configuration

```php
// config/autoload/events.global.php
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
    'dependencies' => [
        'factories' => [
            UserCreatedListener::class => YourListenerFactory::class,
        ],
    ],
];
```

### 5. Dispatch Events

```php
use Psr\EventDispatcher\EventDispatcherInterface;

class UserService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function createUser(array $data): User
    {
        $user = new User($data);

        // Dispatch event
        $event = new Event('user.created', $user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
```

## Documentation

Comprehensive documentation is available in the [docs/](docs/) directory:

- **[Installation Guide](docs/installation.md)** - Detailed installation and setup instructions
- **[Basic Usage](docs/basic-usage.md)** - Getting started with events, listeners, and dispatching
- **[Configuration](docs/configuration.md)** - Configuration options and best practices
- **[Events](docs/events.md)** - Creating and working with events
- **[Listeners](docs/listeners.md)** - Creating and registering event listeners
- **[Subscribers](docs/subscribers.md)** - Using listener subscribers for complex event handling
- **[Priorities](docs/priorities.md)** - Managing listener execution order
- **[Advanced Usage](docs/advanced-usage.md)** - Advanced patterns and techniques
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Testing](docs/testing.md)** - Testing your events and listeners

## Example

A complete example demonstrating all features:

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\Event;

// In your service
class OrderService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function placeOrder(Order $order): void
    {
        // Process order...
        $order->setStatus('completed');

        // Dispatch event with context
        $event = new Event(
            name: 'order.completed',
            target: $order,
            params: [
                'user_id' => $order->getUserId(),
                'total' => $order->getTotal(),
                'timestamp' => time(),
            ]
        );

        $this->dispatcher->dispatch($event);
    }
}

// Listener 1: Send confirmation email
class OrderEmailListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->emailService->sendOrderConfirmation($order);
    }
}

// Listener 2: Update inventory
class OrderInventoryListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $order = $event->getTarget();
        $this->inventoryService->decrementStock($order);
    }
}

// Configuration
return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'order.completed',
            'listener' => OrderEmailListener::class,
            'priority' => ListenerPriority::Normal->value,
        ],
        [
            'event' => 'order.completed',
            'listener' => OrderInventoryListener::class,
            'priority' => ListenerPriority::High->value, // Runs first
        ],
    ],
];
```

## Testing

This library includes comprehensive test suites:

```bash
# Run unit tests
composer test

# Run integration tests
composer test-integration

# Run all tests with coverage
composer test-coverage

# Run all quality checks (CS, PHPStan, tests)
composer check
```

## Code Quality

- **PHPStan Level 10** - Maximum static analysis strictness
- **Laminas Coding Standard** - PSR-12 compliant with additional quality rules
- **100% Type Coverage** - Strict types and comprehensive type hints
- **Comprehensive Tests** - 91 tests covering all functionality

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass: `composer check`
2. Code follows Laminas Coding Standard: `composer cs-check`
3. PHPStan Level 10 passes: `composer static-analysis`
4. New features include tests and documentation

## License

This project is licensed under the BSD-3-Clause License - see the [LICENSE](LICENSE) file for details.

## Credits

- Built on [League Event](https://event.thephpleague.com/) by [The PHP League](https://thephpleague.com/)
- Designed for [Mezzio](https://docs.mezzio.dev/) and [Laminas](https://getlaminas.org/) applications

## Support

- **Issues**: [GitHub Issues](https://github.com/tyrsson/mezzio-eventdispatcher/issues)
- **Source**: [GitHub Repository](https://github.com/tyrsson/mezzio-eventdispatcher)
