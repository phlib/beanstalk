# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- Add new `BuriedException` thrown by `put()` and `release()` when the server
  returns this error. This is a minor **BC break** because these commands
  previously returned a positive response for this error.
- Add new `DrainingException` thrown by `put()` when the server is in draining
  mode and cannot accept new jobs.
  Previously this threw `CommandException` which the new exception extends.
### Changed
- **BC break**: Deprecated static Factory methods are now instance-based.

## [2.1.0] - 2023-07-05
### Added
- Add options to the CLI command to specify a host and port.
  Useful for testing without the need to create a config file.
### Deprecated
- Factory methods will become instance-based with the same name,
  in the next major version.

## [2.0.2] - 2022-09-26
### Fixed
- Kick command type error

## [2.0.1] - 2021-08-14
### Added
- Add specific support for PHP v8
- Type declarations have been added to all method parameters and return types
  where possible. Some methods return mixed type so docblocks are still used.
- Add type definitions docblocks for `Collection::sendToAll()` callbacks.
  There is no change to functionality, but this better explains how these work.
- `put()` and `release()` validate the delay and TTR parameters for integers
  in a valid range.
### Fixed
- `Pool::ignore()` incorrectly updated its own cache of watched tubes, meaning
  multiple calls may have had unexpected results.
### Changed
- **BC break**: Split `Command\Peek(<string>)` to `Command\PeekStatus`.
  `Command\Peek` now only accepts a Job ID integer. Will not impact standard
  implementations which directly use the `Connection::peek*` methods.
  `Command\Peek` status constants are now on `Command\PeekStatus`.
- **BC break**: Restrict `Collection::send*` methods to only accept commands
  defined in `ConnectionInterface`, rather than allowing any method to be called
  on the connection.
- **BC break**: Replace union false return types with nullable types. For
  example, a method that previously hinted `array|false` is now typed `?array`,
  and will return `null` for the same state it previously returned `false`.
- **BC break**: `ValidateTrait::validatePriority()` no longer returns a value.
- Order of stats in `server:stats` CLI command to match order from Beanstalkd.
- **BC break**: Reduce visibility of internal methods and properties. These
  members are not part of the public API. No impact to standard use of this
  package. If an implementation has a use case which needs to override these
  members, please submit a pull request explaining the change.
### Removed
- **BC break**: Removed support for PHP versions <= v7.3 as they are no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.
- **BC break**: Removed cast Command classes as string to reveal raw command.

## [2.0.0] - 2021-07-29

*Release deleted to allow for further changes requiring BC breaks.*

## [1.0.15] - 2019-12-10
### Added
- Add support for *Symfony/Console* v5
- Add a Change Log. Previous releases are shown as date only. See descriptions
  on [project releases page](https://github.com/phlib/beanstalk/releases).

## [1.0.14] - 2018-02-13

## [1.0.13] - 2017-10-30

## [1.0.12] - 2017-02-15

## [1.0.11] - 2016-12-01

## [1.0.10] - 2016-10-14

## [1.0.9] - 2016-08-01

## [1.0.8] - 2016-07-11

## [1.0.7] - 2016-07-08

## [1.0.6] - 2016-07-05

## [1.0.5] - 2016-06-08

## [1.0.4] - 2016-06-03

## [1.0.3] - 2016-03-07

## [1.0.1] - 2016-01-08

## [1.0.0] - 2015-12-09

## [0.2.1] - 2015-11-24

## [0.2.0] - 2015-11-20

## [0.1.2] - 2015-11-06

## [0.1.1] - 2015-11-06

## [0.1] - 2015-11-04
