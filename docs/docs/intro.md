# PHRETS Is...

* a PHP library for PHP developers
* a tool for retrieve real estate-related information from private systems
* only useful for communicating with RETS compliant servers
* easy to install using the [Composer package](https://packagist.org/packages/troydavisson/phrets)
* used by hundreds of software developers
* actively maintained
* well tested through both automated unit and integration tests and real world usage


# PHRETS Isn't...

* a "set it and forget it" installable application (but it could help power one)
* a pre-built website that allows visitors to search available real estate listings (but it could be used to retrieve data to populate one)


# History

The first version of this library was created and released in 2006 in order to help simplify the tedious process of making direct HTTP requests to a RETS server and manually parsing the XML responses.

Since 2006, it has been actively maintained and supported.  In 2015, a major re-write was released as version 2 to better modernize the library using best practices such as [Composer](https://packagist.org/packages/troydavisson/phrets), PHP namespaces, automated tests and [Guzzle](http://guzzle.readthedocs.org/) to make continued use and improvements easier to implement and release.


# RETS?

RETS stands for the **Real Estate Transaction Standard**.  This standard was created in the late 1990s to help facilitate the communication of real estate listing information between different systems.  Today, RETS is primarily used by third party companies to retrieve real estate listings from a local Multiple Listing Service (MLS) database in order to advertise available homes for sale (such as Realtor.com).


# New to real estate data?

If you're a developer jumping into your first project dealing with data from the real estate industry, it's important to know about the restrictions and policies associated with some of this information.  These policies often differ from market to market.

* an MLS system is the local database used by real estate agents to advertise homes to each other.  There are many hundreds of these in the United States alone, and it's not unusual to have multiple systems covering a single large city.
* the MLS system contains listings currently on the market, listings sold (or otherwise no longer available) and anything in between.
* there are often policies which exist that allow agents and authorized third parties to retrieve information about available listings to display to the public.
* there is often information entered into the MLS which is considered private.  For example, the MLS may have the name and phone number for the owner of the property for sale, so even though other information about the listing may be made public, certain details will likely be restricted.


# Why RETS?

Why not FTP?  Why not direct database (SQL) access?

The RETS standard provides a number of different capabilities that would be hard (if not, impossible) to implement using any one existing technology (especially at the time it was created).  Some of those are:

1. the ability to issue custom, "live" queries against the system to return back only the desired information.  This includes both records ("only give me listings in this price range") and fields ("only give me the price and address").
2. the ability to retrieve various media formats.  Through a RETS server, you can often directly download the JPG files associated with particular records.  Some servers go even further by providing other media types such as PDFs, PNGs, virtual tours and HTML code (for embedding YouTube, etc.).
3. the ability to provide updates back to the RETS server.  Although not widely used (yet), the RETS standard defines ways that writes to records can be requested.  Extra metadata provided by RETS servers allows client software to be more familiar with some of the business rules and validation requirements of the MLS.
