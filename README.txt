CONTENTS
--------
* Version
* Introduction
* Program flow
* Configuration
* Cron job
* Future development (roadmap)
* Maintainers

VERSION
-------
1.1     May 25, 2017
1.0     June 1, 2012

INTRODUCTION
------------
Butane Scraper is designed to help locate real estate owned by "absentee owners."
That is, people who have property in one city but they live in another.

The scraper is currently setup to work only on McLennan County Texas Appraisal
District's website. It is not meant to work on all CAD (county appraisal district)
websites, but it might work on a lot of them. In any case, there would be a minimal
amount of customization to get it to work on other sites. Feel free to contact
me for customization help.

PROGRAM FLOW
------------
The file backengine.php is what drives the whole thing. The cron job hits that
script every minute.

  1. Search for a random letter of the alphabet in the owner name field
  2. Select a random page from the search results
  3. Take the property IDs from that page and add them to the database
  4. Select a random property ID from the database and add the owner's name and
  address, adding it to the database
  4a. Do step 4 a random number of times between 1-99
  4b. Pause a varying number of seconds between pulling up properties
  4c. Quit if the elapsed time reaches the set point
  5. Close the engine until the cron job starts it again

SETTINGS
--------

* use_downtimes

This tells the system whether or not to stop scraping during the scheduled down time intervals

* timezone

The time zone where you are located

* tax_year

If for some reason, you wanted to search for the owners of properties in previous
years, you could do that.

* pause_engine

This will prevent the scraper from running, even when the cron job is hitting it

* limit_time

The max length of time that an instance of the scraper engine will run before
shutting down. You want this to be less than php.ini max time setting. Don't worry,
it will start up once the cron job hits it again.

* delay

This is the (rough) amount of time that the engine pauses between looking up
individual properties. Actually, the pauses are randomized, but this setting is
used as a base value for calculating them. It's meant to make the scraper appear
human.

CONFIGURATION
-------------
Set the passcode in backengine.php in $_GET['blerg'] so that it matches what is
in the URL in the cron job.

CRON JOB
--------
* * * * * curl http://www.renthousemogul.com/backengine.php?blerg=11122233344455566677

NOTE-- REPLACE 11122233344455566677 with a real passcode!

FUTURE DEVELOPMENT
------------------
export reports
database structure
get rid of unnecessary files

bug - sometimes it doesn't fill in end time in status
bug - fails to sort downtimes by start_time_hour desc

stats - percent of props residential
stats - number of props updated in last _x_ days

MAINTAINERS
-----------

David Hochhaus
hochh1707@gmail.com
www.hochh.com
