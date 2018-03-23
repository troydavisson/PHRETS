## master

_Release TBD_

## 2.6.1

_Released March 22, 2018_

* Fix header version to correctly reflect the new version

## 2.6

_Released March 22, 2018_

* Support for PHP 7.2
* `PHRETS\Models\Object` renamed to `PHRETS\Models\BaseObject`
* `PHRETS\Models\Metadata\Object` renamed to `PHRETS\Models\Metadata\BaseObject`
* `PHRETS\Parsers\GetMetadata\Object` renamed to `PHRETS\Parsers\GetMetadata\BaseObject`

## 2.5

_Released October 23, 2017_

- Fix RETS 1.8 login response parsing for optional info-token-type
- Support automatic re-login if session issue occurs mid-process
- New option `disable_auto_retry` (bool) added to optionally disable automatic re-login behavior

## 2.4

_Released June 28, 2017_

- Added helper method to more easily work with custom parsers, including custom XML parsers for cleaning data prior to XML parsing

## 2.3

_Released March 31, 2017_

- Guzzle 6+ now required
- New Bulletin methods to access Login details
- Fixes issue with Digest authentication and strict cookie requirements on some servers
- Change to HTTP cookie handling.  Guzzle handling is turned of leaving only cURL to handle cookies.
- Catch XML errors when other Content-Type attributes are also given
- Improve memory usage by reducing references to other objects
- Ignore GetObject responses with the 20403 (No objects found) response code
- Fix an issue with parsing Object metadata with no object types defined

## 2.2

_Released August 10, 2015_

- Access metadata attributes using array syntax
- Include the HTTP 'Accept' header by default

## 2.1

_Released May 4, 2015_

- New Configuration method to force HTTP Basic authentication
- Added methods for easily generating CSV, array and JSON structures directly from results
- Modifications to support both Guzzle 4.x and 5.x
- Loosened version requirements on some Composer dependencies


## 2.0

_Released April 5, 2015_

- Major rewrite
- Added support for RETS 1.8


> **Note**: PHRETS moved to GitHub in 2011 so the commit history since can be [viewed there](https://github.com/troydavisson/PHRETS/commits/master).

## Original

_Released 2006_
