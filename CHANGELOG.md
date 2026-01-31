# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-31

### Added

- Initial release
- `make:actions` Artisan command for generating model action classes
- Base action classes (Index, Show, Store, Update, Delete)
- `Runnable` trait for static and instance action execution
- `run()` helper function for executing actions
- Customizable stubs for action generation
- Configuration file for customizing namespaces and paths
- Support for Laravel 10 and 11
- Comprehensive documentation
- Unit and feature tests

### Features

- Generate all CRUD actions with single command: `php artisan make:actions User`
- Generate specific actions: `--actions=index,store,update`
- Force overwrite existing actions: `--force`
- Custom model namespace support: `--model-path=App\\Domain\\Models`
- Confirmation dialog before overwriting existing actions
- Custom query builder hook for Index actions
- Automatic base action class generation
- Publishable stubs for customization
