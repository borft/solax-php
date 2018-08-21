# solax-php

PHP scraper for Solax PV Cloud

Scrapes performance data from a Solax Solar inverter from the Solax Cloud by using the APP Api

## Configuration
The configuration should be in config/solax-php.ini. To make it even easier, there's an example file (solax-php.ini-example) readily available. I suggest to copy that and add the appropriate settings. Minimal settings rquired:

- database credentials
- solax credentials (username and password)
- pvoutput credentials (site-id and api-key)

## Database
To make it work with persistent db storage:
- create a db (tested on PG)
- import db schema from database.sql
- create a user, and put credentials in the settins file


## Cron
- setup cron to run the import script (scripts/import.php) (for example every 15 minutes)
- setup cron to run the push script (scripts/push_pvoutput.php) every 15 minutes (use a 1 minute offset)

## Requirements:
- PHP >= 7.1 + php-curl + php-PDO (if you want DB functionality)


## Setup on Windows

Dan wrote a short how to, to get it up and running on Windows on the PVOutput forums. The post can be found here: https://forum.pvoutput.org/t/solax-x-hybrid-my-new-inverter-and-pvoutput-question/259/15
