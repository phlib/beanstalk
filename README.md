# phlib/beanstalk

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

// producer
$beanstalk = new Beanstalk('127.0.0.1');
$beanstalk->put(array('my' => 'jobData'));
```

``` php
<?php
use Phlib\Beanstalk\Beanstalk;

// consumer
$beanstalk = new Beanstalk('127.0.0.1');
$job = $beanstalk->reserve();
$myJobData = $job['body'];
$beanstalk->delete($job['id']);
```

### Changing the Job Packager

By default all jobs are packaged as a JSON string from the producer to the consumer.

``` php
<?php
use Phlib\Beanstalk\Beanstalk;
use \Phlib\Beanstalk\JobPackager;

$beanstalk = new Beanstalk('127.0.0.1');
$beanstalk->setJobPackager(new JobPackager\Php);
```

## Configuration

|Name|Type|Required|Default|Description|
|----|----|--------|-------|-----------|
|`host`|*String*|Yes| |Hostname or IP address.|
|`port`|*Integer*|No|`11300`|Beanstalks port.|
|`options`|*Array*|No|`<empty>`|Connection options for Beanstalk.|

### Options

|Name|Type|Default|Description|
|----|----|-------|-----------|
|`timeout`|*Integer*|`60`|The connection timeout.|
