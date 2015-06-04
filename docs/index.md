# PHRETS

Note: If you're looking for version 1, please see the "1.x" branch.  Otherwise, it's highly recommended that you use version 2+.

[![Latest Stable Version](https://poser.pugx.org/troydavisson/PHRETS/v/stable.png)](https://packagist.org/packages/troydavisson/PHRETS)
[![Total Downloads](https://poser.pugx.org/troydavisson/PHRETS/downloads.png)](https://packagist.org/packages/troydavisson/PHRETS)
[![Build Status](https://travis-ci.org/troydavisson/PHRETS.svg?branch=2)](https://travis-ci.org/troydavisson/PHRETS)

[![ScreenShot](http://troda.com/newfeatures.png)](http://youtu.be/115mx-9jYVM)


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

### Introduction

PHRETS provides PHP developers a way to integrate RETS functionality directly within new or existing code by handling the following aspects for you:

* Response parsing (XML, HTTP multipart, etc.)
* Simple variables, arrays and objects returned to the developer
* RETS communication (over HTTP)
* HTTP Header management
* Authentication
* Session/Cookie management
 

### Get Help
The best place to ask for help is in our [Google Group](http://groups.google.com/group/phrets).  Please leave GitHub's issue tracker for bugs with the library.

### Disclaimer  
In many cases, the capabilities provided by this library are dependent on these features being properly implemented by the RETS server you're accessing.  The RETS specification defines how clients and servers communicate, and if a server is doing something unexpected, this library may not work without tweaking some options.
