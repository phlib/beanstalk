# phlib/beanstalk

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
// producer
$beanstalk = new \Phlib\Beanstalk\Beanstalk('127.0.0.1');
$beanstalk->put(array('my' => 'jobData'));
```

``` php
// consumer
$beanstalk = new \Phlib\Beanstalk\Beanstalk('127.0.0.1');
$job = $beanstalk->reserve();

$job['id'];
$job['body'];

```
