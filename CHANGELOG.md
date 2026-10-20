# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-10-20

### Added

- Restores the Fast Forward Container consumer skill through the existing development-only Composer skill manager, alongside the configured contributor skills, without changing runtime dependencies. ([#7](https://github.com/php-fast-forward/event-dispatcher/pull/7))

### Changed

- Automate changelog checks, version pull requests and approved releases with Fast Forward Changelog, with the project skill available to contributors. ([#7](https://github.com/php-fast-forward/event-dispatcher/pull/7))
- Restore the Changelog skill from its GitHub source during Composer installation instead of committing a generated copy and separate installation receipt. ([#7](https://github.com/php-fast-forward/event-dispatcher/pull/7))

## [1.0.0] - 2026-04-06

**Full Changelog**: https://github.com/php-fast-forward/event-dispatcher/commits/v1.0.0

[1.0.1]: https://github.com/php-fast-forward/event-dispatcher/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/php-fast-forward/event-dispatcher/releases/tag/v1.0.0
