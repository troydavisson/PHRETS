[![Build Status](https://travis-ci.org/troydavisson/PHRETS.svg?branch=2)](https://travis-ci.org/troydavisson/PHRETS)

PHRETS
======

PHP client library for interacting with a RETS server to pull real estate listings, photos and other data made available from an MLS system

```php
<?php

date_default_timezone_set('America/New_York');

require_once("vendor/autoload.php");

$log = new \Monolog\Logger('PHRETS');
$log->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));

$config = new \PHRETS\Configuration;
$config->setLoginUrl('rets login url here')
        ->setUsername('rets username here')
        ->setPassword('rets password here')
        ->setRetsVersion('1.7.2');

$rets = new \PHRETS\Session($config);
$rets->setLogger($log);

$connect = $rets->Login();

$system = $rets->GetSystemMetadata();
var_dump($system);

$resources = $system->getResources();
$classes = $resources->first()->getClasses();
var_dump($classes);

$classes = $rets->GetClassesMetadata('Property');
var_dump($classes->first());

$objects = $rets->GetObject('Property', 'Photo', '00-1669', '*', 1);
var_dump($objects);

$fields = $rets->GetTableMetadata('Property', 'A');
var_dump($fields[0]);

$results = $rets->Search('Property', 'A', '*', ['Limit' => 3, 'Select' => 'LIST_1,LIST_105,LIST_15,LIST_22,LIST_87,LIST_133,LIST_134']);
foreach ($results as $r) {
    var_dump($r);
}
```
