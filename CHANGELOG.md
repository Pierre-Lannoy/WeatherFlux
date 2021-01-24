# Changelog
All notable changes to **WeatherFlux** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **WeatherFlux** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - will be 2.0.0]

### Added
- Configuration is now dynamic: you can change it w/o restarting WeatherFlux.
- New log handler for logging in Docker logs (if running in Docker).
- New configuration reloading (default 120s, set `WF_CONF_RELOAD` environment variable to change).
- New statistics publishing (default 600s, set `WF_STAT_PUBLISH` environment variable to change).

### Changed
- [BC] Configuration is now read from `config.json` file.
- Connection to InfluxDB is now tested before using it.
- Static tags and fields now accept configuration per device type (see documentation).
- Better tagging when `precipitation_type` is not specified (Sky and Tempest module).
- Improved startup sequence.
- Improved logging mechanism.
- Improved error handling.

### Removed
- [BC] `config-sample.php` file as it is now unused.

## [1.1.2] - 2021-01-20

### Changed
- Improved autoloading.
- Updated documentation in `README.md`.
- Composer type is now "library".

## [1.1.1] - 2021-01-20

### Changed
- Composer type is now "project".

## [1.1.0] - 2021-01-18

### Added
- New documentation in `README.md`.
- New failsafe if no (or corrupted) config file.
- New changelog file.
- New Code Of Conduct and contribution help.

### Changed
- New releases are now automatically pushed on [Packagist](https://packagist.org/packages/weatherflux/weatherflux).

## [1.0.0] - 2021-01-18

Initial release
