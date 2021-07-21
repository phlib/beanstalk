# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- Type declarations have been added to all method parameters and return types
  where possible. Some methods return mixed type so docblocks are still used.
- Add type definitions docblocks for `Collection::sendToAll()` callbacks.
  There is no change to functionality, but this better explains how these work.
### Changed
- **BC break**: Split `Command\Peek(<string>)` to `Command\PeekStatus`.
  `Command\Peek` now only accepts a Job ID integer. Will not impact standard
  implementations which directly use the `Connection::peek*` methods.
  `Command\Peek` status constants are now on `Command\PeekStatus`.
- **BC break**: Restrict `Collection::send*` methods to only accept commands
  defined in `ConnectionInterface`, rather than allowing any method to be called
  on the connection.
- Order of stats in `server:stats` CLI command to match order from Beanstalkd.
### Removed
- **BC break**: Removed support for PHP versions <= v7.3 as they are no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.

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
