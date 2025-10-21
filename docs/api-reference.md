# API Reference

Complete API documentation for the Mezzio Event Dispatcher library.

## Interfaces

### EventInterface

Base interface for all events:

```php
namespace Webware\Event;

interface EventInterface extends \League\Event\HasEventName
{
    public function getName(): string;
    public function getTarget(): ?object;
    public function getParams(): ?array;
}
```

**Methods:**

- `getName(): string` - Returns the event name identifier
- `eventName(): string` - Alias for `getName()`, required by `HasEventName`
- `getTarget(): ?object` - Returns the target object or null
- `getParams(): ?array` - Returns parameters array or null

### ImmutableEventInterface

Interface for immutable events:

```php
namespace Webware\Event;

interface ImmutableEventInterface extends EventInterface
{
    public function withName(string $name): self;
    public function withTarget(object $target): self;
    public function withParams(array $params): self;
}
```

**Methods:**

- `withName(string $name): self` - Returns new instance with different name
- `withTarget(object $target): self` - Returns new instance with different target
- `withParams(array $params): self` - Returns new instance with different params

**Characteristics:**

- All `with*()` methods return new instances
- Original event remains unchanged
- Thread-safe and predictable

### MutableEventInterface

Interface for mutable events:

```php
namespace Webware\Event;

interface MutableEventInterface extends EventInterface
{
    public function setName(string $name): void;
    public function setTarget(object $target): void;
    public function setParams(array $params): void;
}
```

**Methods:**

- `setName(string $name): void` - Modifies event name in place
- `setTarget(object $target): void` - Modifies target object in place
- `setParams(array $params): void` - Modifies parameters in place

**Characteristics:**

- All `set*()` methods modify the same instance
- No return value (void)
- Useful for progressive enrichment

### ListenerInterface

```php
namespace Webware\Event;

interface ListenerInterface
{
    public function __invoke(EventInterface $event): void;
}
```

**Methods:**

- `__invoke(EventInterface $event): void` - Handles the event

## Classes

### ImmutableEvent

```php
namespace Webware\Event;

readonly class ImmutableEvent implements ImmutableEventInterface
{
    public function __construct(
        private ?string $name = self::class,
        private ?object $target = null,
        private ?array $params = null,
    ) {}
}
```

**Constructor Parameters:**

- `$name` (string|null) - Event name, defaults to class name if null
- `$target` (object|null) - Target object
- `$params` (array|null) - Additional parameters

**Features:**

- Readonly class - all properties are immutable
- Returns self class name if name is null
- Returns empty array for params if null
- All `with*()` methods return new instances
- Thread-safe and predictable

**Example:**

```php
$event = new ImmutableEvent('user.created', $user, ['source' => 'api']);
$modified = $event->withName('user.registered');
// $event still has name 'user.created'
```

### MutableEvent

```php
namespace Webware\Event;

class MutableEvent implements MutableEventInterface
{
    public function __construct(
        private ?string $name = self::class,
        private ?object $target = null,
        private ?array $params = null,
    ) {}
}
```

**Constructor Parameters:**

- `$name` (string|null) - Event name, defaults to class name if null
- `$target` (object|null) - Target object
- `$params` (array|null) - Additional parameters

**Features:**

- Regular class - properties can be modified
- Returns self class name if name is null
- Returns empty array for params if null
- All `set*()` methods modify in place (return void)
- Useful for progressive enrichment

**Example:**

```php
$event = new MutableEvent('order.placed', $order);
$event->setName('order.confirmed');
$event->setParams(['status' => 'confirmed']);
// Same $event instance is modified
```

### ConfigProvider

```php
namespace Webware\Event;

final class ConfigProvider
{
    public function __invoke(): array;
    public function getDependencies(): array;
    public function getListeners(): array;
    public function getSubscribers(): array;
}
```

**Methods:**

- `__invoke(): array` - Returns complete configuration array
- `getDependencies(): array` - Returns dependency injection configuration
- `getListeners(): array` - Returns listener configuration (empty by default)
- `getSubscribers(): array` - Returns subscriber configuration (empty by default)

**Configuration Structure:**

```php
[
    'dependencies' => [
        'aliases' => [
            EventDispatcherInterface::class => EventDispatcher::class,
            ListenerSubscriberInterface::class => ListenerSubscriber::class,
        ],
        'factories' => [
            EventDispatcher::class => EventDispatcherFactory::class,
            ListenerSubscriber::class => ListenerSubscriberFactory::class,
        ],
    ],
    'listeners' => [],
    'subscribers' => [],
]
```

### ListenerSubscriber

```php
namespace Webware\Event;

final readonly class ListenerSubscriber implements
    \League\Event\ListenerSubscriber
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function subscribeListeners(ListenerRegistry $registry): void;
}
```

**Constructor Parameters:**

- `$container` (ContainerInterface) - PSR-11 container for retrieving listeners

**Methods:**

- `subscribeListeners(ListenerRegistry $registry): void` - Registers all configured listeners

**Behavior:**

- Reads listener configuration from `config['listeners']`
- Retrieves listeners from container
- Registers them with the event dispatcher
- Throws `ServiceNotFoundException` if listener not found
- Skips invalid listener specifications

## Enums

### ConfigKey

```php
namespace Webware\Event;

enum ConfigKey: string
{
    case Listeners = 'listeners';
    case Subscribers = 'subscribers';
}
```

**Cases:**

- `Listeners` - Value: `'listeners'`
- `Subscribers` - Value: `'subscribers'`

**Usage:**

```php
$config[ConfigKey::Listeners->value] = [/* ... */];
$config[ConfigKey::Subscribers->value] = [/* ... */];
```

### ListenerPriority

```php
namespace Webware\Event;

enum ListenerPriority: int
{
    case Low = \League\Event\ListenerPriority::LOW;
    case Normal = \League\Event\ListenerPriority::NORMAL;
    case High = \League\Event\ListenerPriority::HIGH;
}
```

**Cases:**

- `Low` - Value: `-100` - Executes last
- `Normal` - Value: `0` - Default priority
- `High` - Value: `100` - Executes first

**Usage:**

```php
'priority' => ListenerPriority::High->value
```

## Factories

### EventDispatcherFactory

```php
namespace Webware\Event\Container;

final class EventDispatcherFactory
{
    public function __invoke(
        ContainerInterface $container
    ): EventDispatcherInterface;
}
```

**Returns:** `Psr\EventDispatcher\EventDispatcherInterface`

**Behavior:**

1. Retrieves `ListenerSubscriber` from container
2. Creates new `League\Event\EventDispatcher`
3. Subscribes the `ListenerSubscriber`
4. Subscribes additional subscribers from configuration
5. Returns the configured dispatcher

### ListenerSubscriberFactory

```php
namespace Webware\Event\Container;

final class ListenerSubscriberFactory
{
    public function __invoke(
        ContainerInterface $container
    ): ListenerSubscriberInterface;
}
```

**Returns:** `League\Event\ListenerSubscriber`

**Behavior:**

- Creates new `ListenerSubscriber` with the container

## Configuration Format

### Listener Configuration

```php
[
    'event' => string,              // Required: Event name
    'listener' => string|class-string, // Required: Service ID
    'priority' => int,              // Optional: Default 0
]
```

**Example:**

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

### Subscriber Configuration

```php
[
    ConfigKey::Subscribers->value => [
        SubscriberClass::class,
        AnotherSubscriber::class,
    ],
]
```

## Exceptions

### ServiceNotFoundException

From `Laminas\ServiceManager\Exception\ServiceNotFoundException`

**Thrown When:**

- A configured listener is not found in the container
- A configured subscriber is not found in the container

**Example:**

```php
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

try {
    $dispatcher->dispatch($event);
} catch (ServiceNotFoundException $e) {
    // Listener not registered in container
    echo $e->getMessage(); // 'Listener "..." not found in container'
}
```

## Type Annotations

The library uses comprehensive PHPDoc annotations for static analysis:

### Array Type Annotations

```php
/**
 * @return array<string, mixed>
 */
public function __invoke(): array;

/**
 * @return array<string, array<class-string, class-string>>
 */
public function getDependencies(): array;

/**
 * @return array<int, array{event: string, listener: class-string, priority?: int}>
 */
public function getListeners(): array;

/**
 * @return array<int, class-string<ListenerSubscriberInterface>>
 */
public function getSubscribers(): array;
```

### Event Interface Annotations

```php
/**
 * @return array<array-key, mixed>|null
 */
public function getParams(): ?array;

/**
 * @param array<array-key, mixed> $params
 */
public function withParams(array $params): self;
```

## PSR Compliance

### PSR-11 Container

The library requires a PSR-11 compatible container:

```php
use Psr\Container\ContainerInterface;

$listener = $container->get(ListenerClass::class);
```

### PSR-14 Event Dispatcher

Events follow PSR-14 standards:

```php
use Psr\EventDispatcher\EventDispatcherInterface;

$dispatcher->dispatch($event);
```

## Integration with League Event

The library builds on League Event:

```php
use League\Event\EventDispatcher;
use League\Event\ListenerPriority as LeagueListenerPriority;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber as ListenerSubscriberInterface;
```

**Key League Event Classes Used:**

- `EventDispatcher` - The actual dispatcher implementation
- `ListenerRegistry` - Registry for subscribing listeners
- `ListenerSubscriber` - Interface for subscriber classes
- `ListenerPriority` - Priority constants

## Version Compatibility

- **PHP:** 8.2, 8.3, 8.4, 8.5
- **league/event:** ^3.0
- **psr/container:** ^2.0
- **beberlei/assert:** ^3.3

## Deprecations

No deprecated APIs in current version.

## Changelog

See [GitHub Releases](https://github.com/tyrsson/mezzio-eventdispatcher/releases) for version history.

## Next Steps

- [Basic Usage](basic-usage.md) - Get started quickly
- [Events](events.md) - Working with events
- [Listeners](listeners.md) - Creating listeners
- [Configuration](configuration.md) - Configuration options
