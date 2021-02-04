# Changelog
All notable changes to **WeatherFlux** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **WeatherFlux** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.4] - 2021-02-04

### Changed
- Improved build process.

### Fixed
- The displayed version in logs is wrong.

## [2.1.3] - 2021-02-04

### Changed
- If WeatherFlux was unable to connect to InfluxDB, it will retry each time the configuration is reloaded - even if there's no changes.

### Fixed
- There's some PHP error or warning with PHP 7.x.
- Some log lines may have (unneeded) extra whitespaces.

### Removed
- Log messages when querying status with `-h` flag.

## [2.1.2] - 2021-01-26

### Changed
- Dissociated logging mechanisms for interractive / Docker in console mode.

## [2.1.1] - 2021-01-26

### Changed
- Improved logging mechanism for console mode in Docker.

## [2.1.0] - 2021-01-26

### Added
- Compatibility with Docker 19+.

## [2.0.5] - 2021-01-26

### Changed
- Improved Docker detection.

## [2.0.4] - 2021-01-26

### Changed
- Improved configuration loading - once again.

## [2.0.3] - 2021-01-26

### Changed
- [BC] Configuration is now in `./config/config.json`.

## [2.0.2] - 2021-01-26

### Changed
- Improved configuration loading.

### Fixed
- Operation modes are inverted.
- Running version number is wrong.

## [2.0.1] - 2021-01-26

### Changed
- Improved bootstrap sequence and auto-loading.

## [2.0.0] - 2021-01-26

### Added
- Configuration is now dynamic: you can change it w/o restarting WeatherFlux.
- New log handler for logging in Docker logs (if running in Docker).
- New configuration reloading (default 120s, set `WF_CONF_RELOAD` environment variable to change).
- New statistics publishing (default 600s, set `WF_STAT_PUBLISH` environment variable to change).
- Exit code management for Docker health-check.
- Events have now a `hit` field allowing to visualize them with scatter plots.

### Changed
- [BC] Configuration is now read from `config.json` file.
- Connection to InfluxDB is now tested before using it.
- Static tags and fields now accept configuration per device type (see documentation).
- Better tagging when `precipitation_type` is not specified (Sky and Tempest module).
- Improved startup sequence.
- Improved logging mechanism.
- Improved error handling.
- Now built on [ObservableWorker](https://github.com/Pierre-Lannoy/ObservableWorker).
- Command line displays usage in case of wrong command/mode.

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
