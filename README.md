# PHRETS

A simple, free, open source PHP library for using [RETS](http://rets.org).

PHP + RETS = PHRETS


## Introduction

PHRETS provides PHP developers a way to integrate RETS functionality directly within new or existing code. A standard class of functions is made available to developers to connect and interact with a server much like they would with other APIs.

PHRETS handles the following aspects of RETS communication for you:
* Response parsing (for other non-XML responses such as HTTP multipart)
* XML Parsing
* Simple variables and arrays returned to the developer
* RETS communication (over HTTP)
* HTTP Header management
* Authentication
* Session/Cookie management


## Download

The source code for PHRETS is available on [GitHub](http://github.com/troydavisson/PHRETS)


## Contribute

PHRETS is maintained in a public Git repository on GitHub.  Issue submissions and pull requests are encouraged if you run into issues or if you have fixes or changes to contribute.


# Documentation

### AddHeader
`AddHeader ( string $name, string $value )`

###### Parameters
    $name - Header name to be passed.
    $value - Header value to be passed.

###### Return Value
Since the header is maintained in a local settings array, this always returns boolean TRUE

###### Changelog
1.0 - If RETS-Version isn't specifically set, it now defaults to RETS/1.5. If User-Agent isn't specifically set, it now defaults to PHRETS/1.0. If Accept isn't specifically set, it now defaults to */*. This makes calling AddHeader() optional prior to connecting

###### Usage Examples
Add a custom User-Agent (needed when using User-Agent Authentication):
```php
$rets->AddHeader("User-Agent", "CustomApp/1.0");
```

###### Related To
[Connect](#connect), [SetParam](#setparam)

