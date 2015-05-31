# Basic "Hello World"

This example shows the minimum of what you'd need to establish a connection to a server:

```php
// set your timezone
date_default_timezone_set('America/New_York');

// pull in the packages managed by Composer
require_once("vendor/autoload.php");

// setup your configuration
$config = new \PHRETS\Configuration;
$config->setLoginUrl('rets login url here')
        ->setUsername('rets username here')
        ->setPassword('rets password here');

// get a session ready using the configuration
$rets = new \PHRETS\Session($config);

// make the first request
$connect = $rets->Login();
```

# Grab some metadata

```php
$system = $rets->GetSystemMetadata();

echo "Server Name: " . $system->getSystemDescription();
```

> Note that unless you have very specific reasons to, it's highly recommended that you use existing tools for looking at metadata provided by a RETS server.  One such tool is [RETSMD.com](http://retsmd.com) (built using PHRETS).

# Grab some records and save in CSV format

**Some assumptions:** (you'll need to verify and/or change these based on the server's metadata)

* Data timestamp field: **LIST_87**
* Property classes: **A**, **B** and **C**

```php
$timestamp_field = 'LIST_87';
$property_classes = ['A', 'B', 'C'];

foreach ($property_classes as $pc) {
    // generate the DMQL query
    $query = "({$timestamp_field}=2000-01-01T00:00:00+)";

    // make the request and get the results
    $results = $rets->Search('Property', $pc, $query);

    // save the results in a local file
    file_put_contents('data/Property_' . $pc . '.csv', $results->toCSV());
}
```
