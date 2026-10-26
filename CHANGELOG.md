# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.2] - 2026-10-26

### Changed

- GitHub Actions(deps): bump php-fast-forward/dev-tools/.github/workflows/wiki-direct.yml from 35d27ee20066fd9e234d228bb866102c64c7ef1a to 84e154046e07cafa6ac09a9e12434b1ff5dcb5e0

  Update dependencies: `php-fast-forward/dev-tools/.github/workflows/wiki-direct.yml`.

  ([#13](https://github.com/php-fast-forward/event-dispatcher/pull/13), @dependabot[bot])

## [1.1.1] - 2026-10-25

### Changed

- Synchronize the bundled local skill automatically and trust Fast Forward Composer
  skill donors through `fast-forward/*`.
  Resolve Changelog contributor skills from stable `0.4.x` releases without a
  redundant skill allowlist.
  Load the project autoloader for PHPDoc checks when DevTools is installed as a
  dependency.

  ([#11](https://github.com/php-fast-forward/event-dispatcher/pull/11))

## [1.1.0] - 2026-10-21

### Added

- Bundle the fast-forward-event-dispatcher consumer skill with executable examples, optional installation guidance and Composer discovery metadata. ([#10](https://github.com/php-fast-forward/event-dispatcher/pull/10))
- Restores the Fast Forward Container consumer skill through the existing development-only Composer skill manager, alongside the configured contributor skills, without changing runtime dependencies. ([#7](https://github.com/php-fast-forward/event-dispatcher/pull/7))

### Changed

- Automate changelog checks, version pull requests and approved releases with Fast Forward Changelog, with the project skill available to contributors. ([#7](https://github.com/php-fast-forward/event-dispatcher/pull/7))
- Synchronize the Config and Container consumer skills from the installed Composer dependencies through an explicit trusted-package list instead of independent GitHub sources. ([#9](https://github.com/php-fast-forward/event-dispatcher/pull/9))
- Require fast-forward/container ^1.10 so installed Composer dependencies supply the supported Container consumer skill alongside the runtime library. ([#9](https://github.com/php-fast-forward/event-dispatcher/pull/9))
- GitHub Actions(deps): Bump php-fast-forward/dev-tools/.github/workflows/wiki-direct.yml from c637e7f9d8ae6059b8655996e56f6d17f874792c to 35d27ee20066fd9e234d228bb866102c64c7ef1a

  Update dependencies: `php-fast-forward/dev-tools/.github/workflows/wiki-direct.yml`.

  ([#6](https://github.com/php-fast-forward/event-dispatcher/pull/6), @dependabot[bot])
- Restore the Changelog skill from its GitHub source during Composer installation instead of committing a generated copy and separate installation receipt. ([#7](https://github.com/php-fast-forward/event-dispatcher/pull/7))

## [1.0.0] - 2026-04-06

**Full Changelog**: https://github.com/php-fast-forward/event-dispatcher/commits/v1.0.0

[1.1.2]: https://github.com/php-fast-forward/event-dispatcher/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/php-fast-forward/event-dispatcher/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/php-fast-forward/event-dispatcher/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/php-fast-forward/event-dispatcher/releases/tag/v1.0.0
