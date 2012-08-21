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
`$name` - Header name to be passed.
`$value` - Header value to be passed.

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


### SetParam
`SetParam ( string $name, string $value )`

###### Parameters
`$name` and `$value` represent setting pairs as described below:
*    *cookie_file* - Path to file to use for temporary session information storage. Default is system temporary file
*    *debug_mode* - Sets cURL output to verbose if True. Default is False.
*    *debug_file* - Sets cURL output file. Default is rets_debug.txt in local folder.
*    *compression_enabled* - Enables GZIP compression if True. Default is False.
*    *force_ua_authentication* - Forces UA authentication when True. Default is False.
*    *disable_follow_location* - In case of PHP safe_mode errors, set to True. Default is False.
*    *force_basic_authentication* - Forces HTTP Basic authentication when True for buggy IIS servers. Default is False.
*    *use_interealty_ua_auth* - Forces use of a different calculation for UA-Auth. Default is False.
*    *disable_encoding_fix* - Disables automatic XML encoding fix if true
*    *catch_last_response* - Enables [GetLastServerResponse()](#getlastserverresponse). Default is False.
*    *offset_support* - Turns on auto-'Offset' feature. Default is False
*    *override_offset_protection* - Disables infinite loop protection of auto-'Offset' feature if true. Default is False.

###### Return Value
Returns TRUE if passed parameter was valid. Returns FALSE otherwise.

###### Changelog
1.0rc2 - Added new parameters `override_offset_protection`, `offset_support`, `catch_last_response` and `disable_encoding_fix`
1.0 - Added new parameters `debug_file`, `force_basic_authentication` and `use_interealty_ua_auth`

###### Usage Examples
```php
$rets->SetParam("debug_mode", true);
$rets->SetParam("debug_file", "debug_log.txt");
```
```php
$rets->SetParam("compression_enabled", true);
```
```php
$rets->SetParam("disable_follow_location", true);
```

###### Related To
[Connect](#connect), [AddHeader](#addheader)


### Connect
`Connect ( string $login_url, string $username, string $password [, string $ua_pwd ] )`

###### Parameters
`$login_url` - Full Login URL to the RETS server
`$username` - Login username
`$password` - Login password
`$ua_pwd` - Optional. User-Agent Password.  Default is blank. If not blank, User-Agent Authentication is forced.

###### Return Value
If any part of the initial connection failed, returns FALSE (check [Error](#error) for details). Otherwise, returns TRUE

###### Changelog
1.0 - Dropped [AddHeader](#addheader) requirements


###### Usage Examples
Connects
```php
$connect = $rets->Connect("http://demo.crt.realtors.org:6103/rets/login", "Joe", "Schmoe");
if (!$connect) {
        print_r($rets->Error());
}
```
Connects with User-Agent Authentication
```php
$connect = $rets->Connect("http://demo.crt.realtors.org:6103/rets/login", "Joe", "Schmoe", "ua-password");
```

###### Related To
[SetParam](#setparam), [AddHeader](#addheader), [Disconnect](#disconnect)


