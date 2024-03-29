# phlib/beanstalk

[![Code Checks](https://img.shields.io/github/actions/workflow/status/phlib/beanstalk/code-checks.yml?logo=github)](https://github.com/phlib/beanstalk/actions/workflows/code-checks.yml)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/beanstalk.svg?logo=codecov)](https://codecov.io/gh/phlib/beanstalk)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/beanstalk.svg?logo=packagist)](https://packagist.org/packages/phlib/beanstalk)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/beanstalk.svg?logo=packagist)](https://packagist.org/packages/phlib/beanstalk)
![Licence](https://img.shields.io/github/license/phlib/beanstalk.svg)

Beanstalkd library implementation.

## Install

Via Composer

``` bash
$ composer require phlib/beanstalk
```

## Basic Usage

``` php
<?php
use Phlib\Beanstalk\Connection;

// producer
$beanstalk = new Connection('127.0.0.1');
$beanstalk->useTube('my-tube');
$beanstalk->put(array('my' => 'jobData'));
```

``` php
<?php
use Phlib\Beanstalk\Connection;

// consumer
$beanstalk = new Connection('127.0.0.1');
$beanstalk->watch('my-tube')
    ->ignore('default');
$job = $beanstalk->reserve();
$myJobData = $job['body'];
$beanstalk->delete($job['id']);
```

## Connection configuration

|Name|Type|Required|Default|Description|
|----|----|--------|-------|-----------|
|`host`|*String*|Yes| |Hostname or IP address.|
|`port`|*Integer*|No|`11300`|Beanstalk's port.|
|`options`|*Array*|No|`<empty>`|Connection options for Beanstalk.|

### Options

|Name|Type|Default|Description|
|----|----|-------|-----------|
|`timeout`|*Integer*|`60`|The connection timeout.|

## Pool configuration

|Name|Type|Required|Default|Description|
|----|----|--------|-------|-----------|
|`connections`|*ConnectionInterface[]*|Yes| |Array of server connections.|
|`retryDelay`|*Integer*|No|`600`|How long to delay retrying a connection for after an error.|
|`logger`|*LoggerInterface*|No| |Optional Logger to capture connection failures.|

## Factory
The factory allows for easy setup of the objects.
This especially useful when creating a pool of beanstalk servers.
The following example lists the various ways it can be used.
The configuration examples in the command line section are created
using the factory.

```php
$factory = new \Phlib\Beanstalk\Factory();

$beanstalk = $factory->create('localhost');

$beanstalk = $factory->createFromArray([
    'host' => 'localhost',
]);

$beanstalk = $factory->createFromArray([
    ['host' => '10.0.0.1'],
    ['host' => '10.0.0.2'],
    ['host' => '10.0.0.3'],
]);
```

### Factory Configuration
The configuration options are as specified above.
With the exception that when creating a pool there is an optional `enabled`.

```php
$factory = new \Phlib\Beanstalk\Factory();

$beanstalk = $factory->createFromArray([
    ['host' => '10.0.0.1', 'enabled' => true],
    ['host' => '10.0.0.2', 'enabled' => false],
    ['host' => '10.0.0.3', 'enabled' => true],
]);
```

## Pool
The pool allows for work to be pushed to and retrieved from multiple servers.
The pool implements the connection interface.

```php
use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Pool;

$connections = [
    new Connection('10.0.0.1'),
    new Connection('10.0.0.2'),
    new Connection('10.0.0.3'),
    new Connection('10.0.0.4'),
];
$logger = new MyLogger();
$pool = new Pool($connections, 120, $logger);

$pool->useTube('my-tube');
$pool->put(array('my' => 'jobData1')); // )
$pool->put(array('my' => 'jobData2')); // )-> distributed between random servers
$pool->put(array('my' => 'jobData3')); // )
```

Alternative way to create a Pool, using the Factory to construct the
connections:

```php
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\Pool;

$connections = (new Factory())->createConnections([
    ['host' => '10.0.0.1', 'enabled' => true],
    ['host' => '10.0.0.2', 'enabled' => false],
    ['host' => '10.0.0.3', 'enabled' => true],
]);
$logger = new MyLogger();
$pool = new Pool($connections, 120, $logger);
```

## Command Line Script

```bash
./vendor/bin/beanstalk
```

Running the script will provide you with a list of options.
Most are self-explanatory.
By default no configuration is required, the script will default to localhost.

### Command Line Configuration

There are 2 ways of specifying a configuration.

1. Create a file called *beanstalk-config.php* either in
   ```/app/root/``` or ```/app/root/config/```.
2. Create a file with a name of your choosing and specify it
   using the command option ```-c /path/to/my/config.php```.

The file must return an array containing the beanstalk configuration.
This configuration will be passed to the Factory to create an instance.

```php
return [
    'host' => '10.0.0.1',
    'port' => 11300
];
```

```php
// pool configuration
return [
    [
        'host' => '10.0.0.1',
        'port' => 11300,
    ],
    [
        'host' => '10.0.0.2',
        'port' => 11300,
    ],
    [
        'host' => '10.0.0.3',
        'port' => 11300,
        'enabled' => false,
    ],
];
```

```php
require_once 'my/app/bootstrap.php';

$app = new MyApp();
return $app['config']['beanstalk'];

```

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
