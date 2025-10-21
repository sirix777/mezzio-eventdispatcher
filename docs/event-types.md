# Event Types: Immutable vs Mutable

This guide helps you understand the differences between immutable and mutable events and when to use each type.

## Overview

The library provides two event implementations:

- **ImmutableEvent** - Readonly events that return new instances when modified
- **MutableEvent** - Regular events that can be modified in place

Both implement specialized interfaces that extend the base `EventInterface`.

## ImmutableEvent

### Characteristics

- **Readonly class** - All properties are immutable after construction
- **Returns new instances** - `with*()` methods create and return new event objects
- **Thread-safe** - Can be safely passed between concurrent operations
- **Predictable** - Original state is always preserved

### Interface

```php
interface ImmutableEventInterface extends EventInterface
{
    public function withName(string $name): self;
    public function withTarget(object $target): self;
    public function withParams(array $params): self;
}
```

### Usage Example

```php
use Webware\Event\ImmutableEvent;

$event = new ImmutableEvent('user.created', $user, ['source' => 'api']);

// Create modified version
$enriched = $event->withParams([
    'source' => 'api',
    'timestamp' => time(),
    'ip_address' => '192.168.1.1',
]);

// Original unchanged
var_dump($event->getParams());     // ['source' => 'api']
var_dump($enriched->getParams());  // ['source' => 'api', 'timestamp' => ..., 'ip_address' => ...]
```

### Method Chaining

```php
$finalEvent = $event
    ->withName('user.registered')
    ->withTarget($verifiedUser)
    ->withParams(['verified' => true]);
```

### When to Use

✅ **Use ImmutableEvent when:**

- Following functional programming principles
- Building event-sourced systems
- Need to preserve event history
- Working in multi-threaded environments
- Debugging complex event flows
- Writing tests that need predictable state
- Following PSR-14 best practices
- Building libraries for others to use

## MutableEvent

### Characteristics

- **Regular class** - Properties can be modified after construction
- **Modifies in place** - `set*()` methods change the same instance
- **Lightweight** - No object allocation for modifications
- **Progressive enrichment** - Multiple listeners can add data to the same event

### Interface

```php
interface MutableEventInterface extends EventInterface
{
    public function setName(string $name): void;
    public function setTarget(object $target): void;
    public function setParams(array $params): void;
}
```

### Usage Example

```php
use Webware\Event\MutableEvent;

$event = new MutableEvent('order.placed', $order);

// Modify in place
$event->setParams(['status' => 'pending']);

// Same instance is modified
var_dump($event->getParams()); // ['status' => 'pending']

// Further modifications
$event->setParams(array_merge($event->getParams(), [
    'payment_method' => 'credit_card',
]));

var_dump($event->getParams()); // ['status' => 'pending', 'payment_method' => 'credit_card']
```

### Sequential Modifications

```php
$event = new MutableEvent('order.processing');

// Multiple listeners can enrich the event
$event->setParams(['step' => 'validation']);
$event->setParams(array_merge($event->getParams(), ['validated' => true]));
$event->setParams(array_merge($event->getParams(), ['step' => 'payment']));
```

### When to Use

✅ **Use MutableEvent when:**

- Performance is critical (avoiding object allocations)
- Building progressive enrichment patterns
- Multiple listeners need to add data to the same event
- Working with legacy code expecting mutable objects
- Implementing event decoration patterns
- Memory constraints are a concern
- Event state accumulation is the goal

## Comparison Table

| Feature | ImmutableEvent | MutableEvent |
|---------|---------------|--------------|
| Modification method | `with*()` | `set*()` |
| Returns | New instance | Void (modifies self) |
| Memory usage | Higher (new objects) | Lower (same object) |
| Thread safety | Yes | No |
| State preservation | Yes | No |
| Chaining | Yes (fluent) | No (void return) |
| Debugging | Easier | Harder |
| Performance | Slower (allocations) | Faster (in-place) |
| PSR-14 alignment | Stronger | Weaker |

## Design Patterns

### Pattern 1: Event Enrichment (Mutable)

```php
use Webware\Event\MutableEvent;

$event = new MutableEvent('order.processing', $order);

// Listener 1: Validate
class ValidateOrderListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $params = $event->getParams();
        $params['validated'] = true;
        $params['validation_time'] = time();
        $event->setParams($params);
    }
}

// Listener 2: Calculate totals
class CalculateTotalsListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $order = $event->getTarget();
        $params = $event->getParams();
        $params['subtotal'] = $order->calculateSubtotal();
        $params['tax'] = $order->calculateTax();
        $params['total'] = $params['subtotal'] + $params['tax'];
        $event->setParams($params);
    }
}
```

### Pattern 2: Event History (Immutable)

```php
use Webware\Event\ImmutableEvent;

$events = [];

// Collect event history
$event1 = new ImmutableEvent('order.created', $order);
$events[] = $event1;

$event2 = $event1->withName('order.validated')
                 ->withParams(['validated_at' => time()]);
$events[] = $event2;

$event3 = $event2->withName('order.paid')
                 ->withParams(array_merge($event2->getParams(), [
                     'paid_at' => time(),
                     'payment_method' => 'credit_card'
                 ]));
$events[] = $event3;

// Replay event history
foreach ($events as $historicalEvent) {
    echo $historicalEvent->getName() . "\n";
}
```

### Pattern 3: Hybrid Approach

```php
// Use immutable for dispatching
use Webware\Event\ImmutableEvent;

$publicEvent = new ImmutableEvent('user.created', $user);
$dispatcher->dispatch($publicEvent);

// Use mutable internally for processing
use Webware\Event\MutableEvent;

class ProcessingContext
{
    private MutableEvent $internalEvent;

    public function process(): void
    {
        $this->internalEvent = new MutableEvent('internal.processing');
        $this->step1();
        $this->step2();
        $this->step3();
    }

    private function step1(): void
    {
        $this->internalEvent->setParams(['step1' => 'complete']);
    }

    private function step2(): void
    {
        $params = $this->internalEvent->getParams();
        $params['step2'] = 'complete';
        $this->internalEvent->setParams($params);
    }
}
```

## Best Practices

### For Immutable Events

1. **Use for public APIs** - External consumers expect stability
2. **Event sourcing** - Store immutable events in event stores
3. **Distributed systems** - Safe to serialize and transmit
4. **Testing** - Easy to assert expected state

```php
// Good: Clear, predictable testing
$event = new ImmutableEvent('user.updated', $user);
$result = $listener($event);
$this->assertSame('user.updated', $event->getName()); // Always true
```

### For Mutable Events

1. **Internal processing** - Use within bounded contexts
2. **Performance-critical paths** - Avoid allocation overhead
3. **Progressive enrichment** - Multiple listeners add data
4. **Document mutation** - Make it clear events are mutable

```php
// Good: Clear mutation intent
/** @var MutableEvent $event */
$event = new MutableEvent('order.processing', $order);
$event->setParams(['status' => 'validating']); // Clearly mutable
```

## Migration Guide

### From Immutable to Mutable

```php
// Before
$event = new ImmutableEvent('order.placed', $order);
$enriched = $event->withParams(['total' => 100]);

// After
$event = new MutableEvent('order.placed', $order);
$event->setParams(['total' => 100]);
// Note: No assignment needed, same instance
```

### From Mutable to Immutable

```php
// Before
$event = new MutableEvent('order.placed', $order);
$event->setParams(['total' => 100]);

// After
$event = new ImmutableEvent('order.placed', $order);
$event = $event->withParams(['total' => 100]);
// Note: Must reassign to capture new instance
```

## Recommendations

### Default Choice: ImmutableEvent

Unless you have specific performance requirements or are implementing progressive enrichment patterns, **start with ImmutableEvent**. It provides:

- Safer default behavior
- Better alignment with PSR-14 principles
- Easier debugging and testing
- More predictable code

### When to Switch: MutableEvent

Consider MutableEvent when you have:

- Measured performance bottlenecks from object allocation
- Clear need for progressive event enrichment
- Bounded contexts where mutation is controlled
- Legacy systems expecting mutable objects

## Summary

Both event types are valid and serve different purposes:

- **ImmutableEvent** prioritizes **safety, predictability, and best practices**
- **MutableEvent** prioritizes **performance and progressive enrichment**

Choose based on your specific requirements, and don't be afraid to use both in different parts of your application where each makes sense.
