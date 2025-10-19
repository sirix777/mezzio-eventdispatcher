# GitHub Copilot Instructions for mezzio-eventdispatcher

## Project Overview

This is a Mezzio/Laminas event dispatcher library that provides PSR-14 event dispatcher support using the League Event package. The library integrates the League Event Dispatcher with Laminas ServiceManager for dependency injection and configuration-based listener/subscriber registration.

**Package Name:** `webware/mezzio-eventdispatcher`
**Namespace:** `Webware\Event`
**Primary Use Case:** Event-driven architecture for Mezzio/Laminas applications

## Core Architecture

### Key Components

1. **EventDispatcher** - League Event Dispatcher wrapped with Laminas ServiceManager integration
2. **EventInterface** - Custom event interface extending League's HasEventName with target and params support
3. **ListenerInterface** - Simple listener interface for event handlers
4. **ListenerSubscriber** - Aggregates listeners from configuration
5. **ConfigProvider** - Laminas configuration provider for service registration

### Design Patterns

- **Factory Pattern** - All services are created via factories (EventDispatcherFactory, ListenerSubscriberFactory)
- **Subscriber Pattern** - Events and listeners follow PSR-14 event dispatcher pattern
- **Dependency Injection** - Full integration with PSR-11 containers (Laminas ServiceManager)

## Code Standards & Quality

### PHP Version Support
- **Required:** PHP 8.2, 8.3, 8.4, or 8.5
- Always use strict types: `declare(strict_types=1);`
- Use modern PHP 8.2+ features when appropriate (readonly properties, enums, etc.)

### Coding Standards
- Follow **Laminas Coding Standard** (enforced via PHPCS)
- All code must pass: `composer cs-check`
- Auto-fix issues with: `composer cs-fix`
- Final classes by default unless inheritance is explicitly needed
- Use typed properties and return types everywhere
- Use constructor property promotion when applicable

### Static Analysis
- **PHPStan Level 10** (maximum strictness)
- All code must pass: `composer static-analysis`
- Use stub files for external dependencies in `stubs/` directory
- Set `treatPhpDocTypesAsCertain: false` for maximum type safety

### Testing Requirements

**CRITICAL:** All testing must be performed by running the test suite from composer.json:

- **Unit Tests:** `composer test`
- **Integration Tests:** `composer test-integration`
- **Full Check:** `composer check` (runs cs-check, static-analysis, test, and test-integration)
- **Coverage:** `composer test-coverage`

Test files are organized as:
- Unit tests: `test/unit/` (namespace: `WebwareTest\`)
- Integration tests: `test/integration/` (namespace: `WebwareIntegrationTest\`)

When writing tests:
- Use PHPUnit 11.5+
- Follow PSR-4 autoloading conventions
- Integration tests should use real container setup (see `test/integration/TestAsset/SetupTrait.php`)
- Always use `--colors=always` flag (already in composer scripts)

### Documentation
- Use PHPDoc blocks for all public APIs
- Document `@param`, `@return`, and `@throws` tags
- Include `@psalm-*` or `@phpstan-*` annotations when needed for complex types

## Configuration Structure

### ConfigProvider Pattern
The library uses Laminas ConfigProvider pattern:

```php
return [
    'dependencies' => [
        'aliases' => [...],
        'factories' => [...],
    ],
    ConfigKey::Listeners->value => [...],   // 'listeners'
    ConfigKey::Subscribers->value => [...], // 'subscribers'
];
```

### ConfigKey Enum
Use the `ConfigKey` enum for configuration keys:
```php
enum ConfigKey: string
{
    case Listeners = 'listeners';
    case Subscribers = 'subscribers';
}
```
- `ConfigKey::Listeners->value` - For event listener configuration
- `ConfigKey::Subscribers->value` - For event subscriber configuration

## Event System

### Creating Events
Events should implement `EventInterface`:
```php
interface EventInterface extends HasEventName
{
    public function getName(): string;
    public function withName(string $name): self;
    public function getTarget(): ?object;
    public function withTarget(object $target): self;
    public function getParams(): ?array;
    public function withParams(array $params): self;
}
```

Events should be immutable - use `with*()` methods to create modified copies.

### Creating Listeners
Listeners should implement `ListenerInterface`:

```php
interface ListenerInterface
{
    public function __invoke(EventInterface $event): void;
}
```

### Listener Priorities
Use the `ListenerPriority` enum for standardized priorities:
- Specific values are defined in `src/ListenerPriority.php`
- Always use enum values, not magic numbers

## Dependencies

### Core Dependencies
- `league/event` ^3.0 - PSR-14 event dispatcher implementation
- `beberlei/assert` ^3.3 - Runtime assertions
- `psr/container` ^2.0 - PSR-11 container interface

### Development Dependencies
- Laminas Coding Standard
- PHPStan with PHPUnit extension
- PHPUnit 11.5+
- Laminas ServiceManager (for integration testing)

## File Organization

### Source Files (`src/`)
- Root level: Core interfaces and implementations
- `Container/` subdirectory: Factory classes for DI container

### Test Files (`test/`)
- `unit/` - Isolated unit tests
- `integration/` - Tests with real container/service manager
- `integration/TestAsset/` - Test fixtures and helpers

### Stubs (`stubs/`)
- PHPStan stub files for external dependencies
- Organized by vendor/package structure

## Common Tasks

### Adding a New Event
1. Implement `EventInterface`
2. Make the event immutable (all properties readonly or private with `with*()` methods)
3. Include in appropriate namespace under `Webware\Event`
4. Add unit tests in `test/unit/`
5. Run `composer check` to validate

### Adding a New Listener
1. Implement `ListenerInterface`
2. Type-hint the specific event in `__invoke()`
3. Register in application's `ConfigProvider` under `ConfigKey::Listeners`
4. Add integration tests in `test/integration/`
5. Run `composer check` to validate

### Adding a New Subscriber
1. Implement League's `ListenerSubscriber` interface
2. Define listener subscriptions in `getListenersForEvent()` method
3. Register in application's `ConfigProvider` under `ConfigKey::Subscribers`
4. Ensure it's registered as a service in container
5. Add integration tests
6. Run `composer check` to validate

## Best Practices

1. **Immutability** - Events should be immutable, use `with*()` pattern
2. **Type Safety** - Always use strict types and specific type hints
3. **Final Classes** - Make classes final unless designed for extension
4. **Factory Pattern** - Create services via factories, not direct instantiation
5. **Container Integration** - Leverage PSR-11 container for all dependencies
6. **Testing First** - Write tests using composer scripts, ensure all pass before committing
7. **Static Analysis** - Code must pass PHPStan level 10
8. **Coding Standards** - Code must pass Laminas Coding Standard checks

## Important Notes

- This library is designed for Mezzio/Laminas applications
- It bridges League Event with Laminas ServiceManager
- Configuration-based listener/subscriber registration is the primary usage pattern
- All public APIs should maintain backward compatibility
- License: BSD-3-Clause

## Quick Reference Commands

```bash
composer check              # Run all checks (CS, static analysis, tests)
composer cs-check          # Check coding standards
composer cs-fix            # Fix coding standard violations
composer test              # Run unit tests
composer test-integration  # Run integration tests
composer test-coverage     # Generate coverage report
composer static-analysis   # Run PHPStan
```

**Remember:** Always run `composer check` before committing code!
