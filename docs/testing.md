# Testing

This guide covers testing strategies for events, listeners, and the event dispatcher in your applications.

## Testing Philosophy

Event-driven architectures require comprehensive testing at multiple levels:

1. **Unit Tests** - Test individual listeners in isolation
2. **Integration Tests** - Test event dispatch and listener execution
3. **Functional Tests** - Test complete workflows with events

## Testing Events

### Basic Event Testing

```php
use PHPUnit\Framework\TestCase;
use Webware\Event\Event;

class EventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $event = new Event('test.event');

        $this->assertSame('test.event', $event->getName());
        $this->assertNull($event->getTarget());
        $this->assertSame([], $event->getParams());
    }

    public function testEventWithTarget(): void
    {
        $target = new \stdClass();
        $event = new Event('test.event', $target);

        $this->assertSame($target, $event->getTarget());
    }

    public function testEventImmutability(): void
    {
        $event = new Event('original');
        $modified = $event->withName('modified');

        $this->assertNotSame($event, $modified);
        $this->assertSame('original', $event->getName());
        $this->assertSame('modified', $modified->getName());
    }
}
```

### Testing Custom Events

```php
class UserRegisteredEventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $user = new User(['id' => 1, 'email' => 'test@example.com']);
        $event = new UserRegisteredEvent($user);

        $this->assertSame('user.registered', $event->getName());
        $this->assertSame($user, $event->getUser());
    }

    public function testEventWithMetadata(): void
    {
        $user = new User(['id' => 1]);
        $metadata = ['ip_address' => '192.168.1.1'];
        $event = new UserRegisteredEvent($user, $metadata);

        $this->assertSame('192.168.1.1', $event->getIpAddress());
    }
}
```

## Testing Listeners

### Unit Testing Listeners

Test listeners in complete isolation with mocks:

```php
use PHPUnit\Framework\TestCase;
use Webware\Event\Event;

class WelcomeEmailListenerTest extends TestCase
{
    public function testSendsWelcomeEmail(): void
    {
        $user = new User(['email' => 'test@example.com']);

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())
            ->method('sendWelcome')
            ->with($user);

        $listener = new WelcomeEmailListener($emailService);

        $event = new Event('user.created', $user);
        $listener($event);
    }

    public function testHandlesEmailFailureGracefully(): void
    {
        $user = new User(['email' => 'test@example.com']);

        $emailService = $this->createMock(EmailService::class);
        $emailService->method('sendWelcome')
            ->willThrowException(new \RuntimeException('SMTP error'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send welcome email',
                $this->anything()
            );

        $listener = new WelcomeEmailListener($emailService, $logger);

        $event = new Event('user.created', $user);

        // Should not throw
        $listener($event);
    }
}
```

### Testing Listener Logic

```php
class OrderProcessListenerTest extends TestCase
{
    public function testProcessesValidOrder(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('isValid')->willReturn(true);

        $orderService = $this->createMock(OrderService::class);
        $orderService->expects($this->once())
            ->method('process')
            ->with($order);

        $listener = new OrderProcessListener($orderService);

        $event = new Event('order.placed', $order);
        $listener($event);
    }

    public function testSkipsInvalidOrder(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('isValid')->willReturn(false);

        $orderService = $this->createMock(OrderService::class);
        $orderService->expects($this->never())
            ->method('process');

        $listener = new OrderProcessListener($orderService);

        $event = new Event('order.placed', $order);
        $listener($event);
    }
}
```

## Testing Subscribers

### Unit Testing Subscriber Registration

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
            $this->createMock(EmailService::class)
        );

        $subscriber->subscribeListeners($registry);
    }
}
```

### Testing Subscriber Methods

```php
class UserEventSubscriberTest extends TestCase
{
    public function testOnUserCreatedSendsEmail(): void
    {
        $user = new User(['email' => 'test@example.com']);

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())
            ->method('sendWelcome')
            ->with($user);

        $subscriber = new UserEventSubscriber($emailService);

        $event = new Event('user.created', $user);
        $subscriber->onUserCreated($event);
    }
}
```

## Integration Testing

### Testing Event Dispatch

```php
class EventDispatcherIntegrationTest extends TestCase
{
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->container = $this->createConfiguredContainer();
        $this->dispatcher = $this->container->get(EventDispatcherInterface::class);
    }

    public function testEventIsDispatchedToListeners(): void
    {
        $user = new User(['id' => 1]);
        $event = new Event('user.created', $user);

        $this->dispatcher->dispatch($event);

        // Assert side effects
        $this->assertDatabaseHas('audit_log', [
            'event' => 'user.created',
            'user_id' => 1,
        ]);
    }

    public function testMultipleListenersExecute(): void
    {
        $executionLog = [];

        // Configure container with test listeners that log execution
        // ... setup code ...

        $event = new Event('test.event');
        $this->dispatcher->dispatch($event);

        $this->assertCount(3, $executionLog);
        $this->assertContains('ListenerOne', $executionLog);
        $this->assertContains('ListenerTwo', $executionLog);
        $this->assertContains('ListenerThree', $executionLog);
    }
}
```

### Testing Priority Order

```php
class ListenerPriorityTest extends TestCase
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
        $dispatcher->subscribeTo('test', $low, ListenerPriority::Low->value);
        $dispatcher->subscribeTo('test', $normal, ListenerPriority::Normal->value);

        $dispatcher->dispatch(new Event('test'));

        $this->assertSame(['high', 'normal', 'low'], $executionOrder);
    }
}
```

## Testing with Containers

### Creating Test Containers

```php
trait TestContainerTrait
{
    private function createTestContainer(array $config = []): ContainerInterface
    {
        $defaultConfig = [
            'dependencies' => [
                'factories' => [
                    TestListener::class => InvokableFactory::class,
                    TestSubscriber::class => InvokableFactory::class,
                ],
            ],
            ConfigKey::Listeners->value => [],
            ConfigKey::Subscribers->value => [],
        ];

        $mergedConfig = array_merge_recursive($defaultConfig, $config);

        return new ServiceManager($mergedConfig['dependencies']);
    }
}
```

### Testing Configuration Loading

```php
class ConfigurationTest extends TestCase
{
    public function testConfigProviderReturnsValidStructure(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey(ConfigKey::Listeners->value, $config);
        $this->assertArrayHasKey(ConfigKey::Subscribers->value, $config);
    }

    public function testEventDispatcherIsRegistered(): void
    {
        $container = $this->createTestContainer();

        $this->assertTrue($container->has(EventDispatcherInterface::class));

        $dispatcher = $container->get(EventDispatcherInterface::class);
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }
}
```

## Test Doubles

### Mock Listeners

```php
class MockListener implements ListenerInterface
{
    public bool $wasCalled = false;
    public ?EventInterface $receivedEvent = null;

    public function __invoke(EventInterface $event): void
    {
        $this->wasCalled = true;
        $this->receivedEvent = $event;
    }

    public function assertWasCalled(): void
    {
        PHPUnit::assertTrue($this->wasCalled, 'Listener was not called');
    }

    public function assertReceivedEvent(string $name): void
    {
        PHPUnit::assertNotNull($this->receivedEvent);
        PHPUnit::assertSame($name, $this->receivedEvent->getName());
    }
}
```

### Spy Listeners

```php
class SpyListener implements ListenerInterface
{
    public array $events = [];

    public function __invoke(EventInterface $event): void
    {
        $this->events[] = [
            'name' => $event->getName(),
            'target' => $event->getTarget(),
            'params' => $event->getParams(),
            'timestamp' => microtime(true),
        ];
    }

    public function getEventCount(): int
    {
        return count($this->events);
    }

    public function getLastEvent(): ?array
    {
        return end($this->events) ?: null;
    }
}
```

## Testing Async Operations

### Testing Queued Listeners

```php
class QueuedListenerTest extends TestCase
{
    public function testListenerQueuesJob(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('push')
            ->with($this->isInstanceOf(ProcessOrderJob::class));

        $listener = new QueuedOrderListener($queue);

        $order = new Order(['id' => 1]);
        $event = new Event('order.placed', $order);

        $listener($event);
    }
}
```

## Test Helpers

### Event Factory

```php
class EventFactory
{
    public static function createUserEvent(
        string $name = 'user.created',
        ?User $user = null
    ): Event {
        return new Event($name, $user ?? self::createUser());
    }

    public static function createUser(array $data = []): User
    {
        return new User(array_merge([
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], $data));
    }
}

// Usage
$event = EventFactory::createUserEvent();
```

### Listener Assertions

```php
trait ListenerAssertions
{
    protected function assertListenerWasCalled(
        MockListener $listener,
        string $message = ''
    ): void {
        $this->assertTrue(
            $listener->wasCalled,
            $message ?: 'Expected listener to be called'
        );
    }

    protected function assertEventDispatched(
        string $eventName,
        callable $assertion = null
    ): void {
        // Implementation depends on your testing setup
        // Could check logs, database, or use a spy dispatcher
    }
}
```

## Best Practices

1. **Test in Isolation** - Unit test listeners without dispatching
2. **Mock Dependencies** - Use mocks for external services
3. **Test Side Effects** - Verify listener actions, not just calls
4. **Integration Tests** - Test complete event flows
5. **Test Error Handling** - Verify graceful degradation
6. **Test Priorities** - Ensure correct execution order
7. **Use Factories** - Create consistent test data
8. **Document Tests** - Explain what each test verifies

## Common Testing Patterns

### Arrange-Act-Assert

```php
public function testExample(): void
{
    // Arrange
    $user = new User(['id' => 1]);
    $event = new Event('user.created', $user);
    $listener = new TestListener($this->createMock(Service::class));

    // Act
    $listener($event);

    // Assert
    $this->assertTrue($listener->wasProcessed());
}
```

### Given-When-Then

```php
public function testExample(): void
{
    // Given a user created event
    $event = new Event('user.created', new User(['id' => 1]));

    // When the event is dispatched
    $this->dispatcher->dispatch($event);

    // Then a welcome email should be sent
    $this->assertEmailSent('test@example.com', 'Welcome');
}
```

## Next Steps

- [Advanced Usage](advanced-usage.md) - Advanced patterns
- [API Reference](api-reference.md) - Complete API documentation
