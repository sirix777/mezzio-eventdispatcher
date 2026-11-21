# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-11-22

### Added
- PHP 8.6 support.

### Changed
- Development tooling: PHPUnit `^12.5`; mocks without expectations are now
  stubs, and container mocks that check arguments with `with()` now declare
  `once()`. The PHPStan CI job runs on PHP 8.3. `composer.lock` is no longer
  tracked.

### Removed
- PHP 8.2 support. Supported versions are now PHP 8.3–8.6.

## [0.1.0]

- Initial release of the `sirix/mezzio-eventdispatcher` fork.
