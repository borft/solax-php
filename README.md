# solax-php

PHP scraper for Solax PV Cloud

Scrapes performance data from a Solax Solar inverter from the Solax Cloud by using the APP Api

To make it work with persistent db storage:
- create a db (tested on PG)
- import db schema from database.sql
-  create a user, and put credentials in the import (example-db.php) and pv_push scripts
- add api-key (not read-only) and site-id to pv_push script

- setup cron to run the import scripts (for example every 15 minutes)
- setup cron to run the push script every 15 minutes (use a 1 minute offset)

Requirements:
- PHP >= 7.1 + php-curl + php-PDO (if you want DB functionality)


