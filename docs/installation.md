# Installation Guide

This guide will walk you through installing and configuring the Mezzio Event Dispatcher library.

## Requirements

- PHP 8.2, 8.3, 8.4, or 8.5
- Composer
- A PSR-11 Container implementation (Laminas ServiceManager recommended)

## Installation Steps

### 1. Install via Composer

```bash
composer require webware/mezzio-eventdispatcher
```

This will install the library and its dependencies:

- `league/event` ^3.0 - PSR-14 Event Dispatcher implementation
- `beberlei/assert` ^3.3 - Runtime assertions
- `psr/container` ^2.0 - PSR-11 Container interface

### 2. Register the Configuration Provider

The library uses Laminas ConfigProvider pattern for service registration.

#### For Mezzio Applications

If you're using Mezzio with laminas-config-aggregator:

```php
// config/config.php
use Laminas\ConfigAggregator\ConfigAggregator;
use Webware\Event\ConfigProvider;

$aggregator = new ConfigAggregator([
    // Load application config first
    ConfigProvider::class,

    // Your application providers
    App\ConfigProvider::class,

    // Load development config if it exists
    new ArrayProvider($developmentConfig ?? []),
]);

return $aggregator->getMergedConfig();
```

### 3. Verify Installation

After installation, verify the services are registered correctly:

```php
use League\Event\EventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

// Get from your container
$dispatcher = $container->get(EventDispatcherInterface::class);

// Should return an instance of EventDispatcher
var_dump($dispatcher instanceof EventDispatcher); // bool(true)
```

## What Gets Registered

The ConfigProvider registers the following services:

### Aliases

- `Psr\EventDispatcher\EventDispatcherInterface` → `League\Event\EventDispatcher`
- `League\Event\ListenerSubscriber` → `Webware\Event\ListenerSubscriber`

### Factories

- `League\Event\EventDispatcher` - Created by `EventDispatcherFactory`
- `Webware\Event\ListenerSubscriber` - Created by `ListenerSubscriberFactory`

### Configuration Keys

- `listeners` - Array of listener configurations
- `subscribers` - Array of subscriber class names

## Next Steps

Now that you have the library installed:

1. [Learn Basic Usage](basic-usage.md) - Create your first events and listeners
2. [Configure Listeners](configuration.md) - Set up your event listeners
3. [Create Events](events.md) - Build custom event classes

## Troubleshooting

### Container Not Found Exception

If you get an error about the container not finding services:

1. Ensure your ConfigProvider is registered
2. Clear any configuration cache
3. Verify your container is properly configured

### Class Not Found

If you get class not found errors:

1. Run `composer dump-autoload`
2. Verify the package is in your `vendor/` directory
3. Check your PHP version meets requirements

### Configuration Not Loading

If your listeners aren't being registered:

1. Check the ConfigProvider is in your config aggregator
2. Verify the configuration file syntax
3. Ensure `ConfigKey::Listeners->value` is used for the key

## Development Installation

For contributing to the library:

```bash
# Clone the repository
git clone https://github.com/tyrsson/mezzio-eventdispatcher.git
cd mezzio-eventdispatcher

# Install dependencies
composer install

# Run tests
composer test

# Check code quality
composer check
```
