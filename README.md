# phlib/beanstalk

[![Build Status](https://img.shields.io/travis/phlib/beanstalk/master.svg)](https://travis-ci.org/phlib/beanstalk)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/beanstalk.svg)](https://packagist.org/packages/phlib/beanstalk)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/beanstalk.svg)](https://packagist.org/packages/phlib/beanstalk)

Beanstalkd library implementation.

## Install

Via Composer

``` bash
$ composer require phlib/beanstalk
```
or
``` JSON
"require": {
    "phlib/beanstalk": "*"
}
```

## Basic Usage

``` php
<?php
use Phlib\Beanstalk\Beanstalk;
use Phlib\Beanstalk\Socket;

// producer
$beanstalk = new Beanstalk(new Socket('127.0.0.1'));
$beanstalk->useTube('my-tube');
$beanstalk->put(array('my' => 'jobData'));
```

``` php
<?php
use Phlib\Beanstalk\Beanstalk;
use Phlib\Beanstalk\Socket;

// consumer
$beanstalk = new Beanstalk(new Socket('127.0.0.1'));
$beanstalk->watch('my-tube')
    ->ignore('default');
$job = $beanstalk->reserve();
$myJobData = $job['body'];
$beanstalk->delete($job['id']);
```

### Changing the Job Packager

By default all jobs are packaged as a JSON string from the producer to the consumer.

``` php
<?php
use Phlib\Beanstalk\Beanstalk;
use Phlib\Beanstalk\JobPackager;

$beanstalk = new Beanstalk('127.0.0.1');
$beanstalk->setJobPackager(new JobPackager\Php);
```

## Configuration

|Name|Type|Required|Default|Description|
|----|----|--------|-------|-----------|
|`host`|*String*|Yes| |Hostname or IP address.|
|`port`|*Integer*|No|`11300`|Beanstalk's port.|
|`options`|*Array*|No|`<empty>`|Connection options for Beanstalk.|

### Options

|Name|Type|Default|Description|
|----|----|-------|-----------|
|`timeout`|*Integer*|`60`|The connection timeout.|

## Command Line Script

```bash
./vendor/bin/beanstalk-cli
```

Running the script will provide you with a list of options. Most are self explanatory. By default no configuration is 
required, the script will assume localhost.

### Command Line Configuration

There 2 ways of specifying a configuration.

1. Create a file called beanstalk-config.php either in ```/app/root``` or ```/app/root/config```.
2. Create a file with a name of your choosing and specify it using the command option ```-c /path/to/my/config.php```

The file must return an array containing the beanstalk configuration.

```php
return [
    'host' => '10.0.0.1',
    'port' => 11300
];
```

```php
return [
    'server' => [
        'host' => '10.0.0.1',
        'port' => 11300
    ],
    'packager' => 'Json'
];
```

```php
// pool configuration
return [
    'servers' => [
        [
            'host' => '10.0.0.1',
            'port' => 11300
        ],
        [
            'host' => '10.0.0.2',
            'port' => 11300
        ],
        [
            'host' => '10.0.0.3',
            'port' => 11300,
            'enabled' => false
        ]
    ],
    'packager' => 'Json'
];
```

```php
require_once 'my/app/bootstrap.php';

$app = new MyApp();
return $app['config']['beanstalk'];

```
