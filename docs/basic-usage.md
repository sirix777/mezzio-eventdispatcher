# Basic Usage

This guide covers the fundamental concepts and basic usage patterns of the Mezzio Event Dispatcher.

## Core Concepts

The library revolves around three main components:

1. **Events** - Objects representing something that happened
2. **Listeners** - Callables that respond to events
3. **Dispatcher** - Coordinates event distribution to listeners

## Creating Events

The library provides two event implementations:

### ImmutableEvent (Recommended)

Immutable events return new instances when modified:

```php
use Webware\Event\ImmutableEvent;

// Simple event with just a name
$event = new ImmutableEvent('user.registered');

// Event with a target object
$event = new ImmutableEvent('user.registered', $user);

// Event with target and parameters
$event = new ImmutableEvent(
    name: 'user.registered',
    target: $user,
    params: [
        'ip_address' => '192.168.1.1',
        'timestamp' => time(),
    ]
);
```

### MutableEvent

Mutable events modify in place:

```php
use Webware\Event\MutableEvent;

$event = new MutableEvent('user.registered', $user);
$event->setParams(['timestamp' => time()]);
// Same instance is modified
```

### Event Properties

Events support three properties:

- **name** - String identifier for the event type
- **target** - Optional object that is the subject of the event
- **params** - Optional array of additional data

## Creating Listeners

Listeners are callables that handle events. The simplest form implements `ListenerInterface`:

```php
use Webware\Event\EventInterface;
use Webware\Event\ListenerInterface;

class WelcomeEmailListener implements ListenerInterface
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        $this->emailService->sendWelcome($user);
    }
}
```

### Listener Registration

Listeners must be registered as services in your container:

```php
// config/autoload/dependencies.global.php
return [
    'dependencies' => [
        'factories' => [
            WelcomeEmailListener::class => WelcomeEmailListenerFactory::class,
        ],
    ],
];
```

## Configuring Event Listeners

Register listeners for events via configuration:

```php
// config/autoload/events.global.php
use Webware\Event\ConfigKey;
use Webware\Event\ListenerPriority;

return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'user.registered',
            'listener' => WelcomeEmailListener::class,
            'priority' => ListenerPriority::Normal->value,
        ],
    ],
];
```

### Configuration Keys

- `event` - The event name to listen for
- `listener` - The service ID/class name of the listener
- `priority` - Optional priority (defaults to `ListenerPriority::Normal`)

## Dispatching Events

Inject the event dispatcher and dispatch events:

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\ImmutableEvent;

class UserService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function register(array $data): User
    {
        $user = new User($data);
        $user->save();

        // Dispatch immutable event
        $event = new ImmutableEvent('user.registered', $user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
```

## Multiple Listeners

Multiple listeners can respond to the same event:

```php
return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'user.registered',
            'listener' => WelcomeEmailListener::class,
        ],
        [
            'event' => 'user.registered',
            'listener' => UserActivityLogListener::class,
        ],
        [
            'event' => 'user.registered',
            'listener' => SendToAnalyticsListener::class,
        ],
    ],
];
```

All listeners will be called when the event is dispatched.

## Modifying Events

### With Immutable Events

Use `with*()` methods to create new instances:

```php
use Webware\Event\ImmutableEvent;

$event = new ImmutableEvent('user.updated', $user);

// Create a new event with different name
$renamedEvent = $event->withName('user.modified');

// Create a new event with different target
$newEvent = $event->withTarget($newUser);

// Create a new event with different params
$enrichedEvent = $event->withParams(['action' => 'profile_update']);

// Chain methods
$modifiedEvent = $event
    ->withName('user.profile.updated')
    ->withParams(['field' => 'email']);

// Original event remains unchanged
echo $event->getName(); // 'user.updated'
```

### With Mutable Events

Use `set*()` methods to modify in place:

```php
use Webware\Event\MutableEvent;

$event = new MutableEvent('user.updated', $user);

// Modify the same instance
$event->setName('user.modified');
$event->setTarget($newUser);
$event->setParams(['action' => 'profile_update']);

// Sequential modifications
$event->setName('user.profile.updated');
$event->setParams(array_merge($event->getParams(), ['field' => 'email']));

// Same instance was modified
echo $event->getName(); // 'user.profile.updated'
```

## Accessing Event Data

In your listeners, access event data:

```php
class UserActivityListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        // Get the event name
        $eventName = $event->getName(); // 'user.registered'

        // Get the target object
        $user = $event->getTarget(); // User instance or null

        // Get parameters
        $params = $event->getParams(); // array or []

        // Access specific parameter
        $timestamp = $params['timestamp'] ?? time();
    }
}
```

## Complete Example

Here's a complete example putting it all together:

```php
// 1. Create the event class (optional, can use Event directly)
class UserRegisteredEvent extends Event
{
    public function __construct(User $user)
    {
        parent::__construct('user.registered', $user);
    }

    public function getUser(): User
    {
        return $this->getTarget();
    }
}

// 2. Create listeners
class WelcomeEmailListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        // Send welcome email
    }
}

class SetupAccountListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $user = $event->getTarget();
        // Create user directory, preferences, etc.
    }
}

// 3. Configure listeners
return [
    ConfigKey::Listeners->value => [
        [
            'event' => 'user.registered',
            'listener' => SetupAccountListener::class,
            'priority' => ListenerPriority::High->value, // Run first
        ],
        [
            'event' => 'user.registered',
            'listener' => WelcomeEmailListener::class,
            'priority' => ListenerPriority::Normal->value,
        ],
    ],
    'dependencies' => [
        'factories' => [
            WelcomeEmailListener::class => InvokableFactory::class,
            SetupAccountListener::class => InvokableFactory::class,
        ],
    ],
];

// 4. Use in your service
class UserService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function register(array $data): User
    {
        $user = User::create($data);

        $event = new UserRegisteredEvent($user);
        $this->dispatcher->dispatch($event);

        return $user;
    }
}
```

## Next Steps

- [Configuration Guide](configuration.md) - Advanced configuration options
- [Events Documentation](events.md) - Deep dive into events
- [Listeners Documentation](listeners.md) - Advanced listener patterns
- [Priorities](priorities.md) - Control execution order
