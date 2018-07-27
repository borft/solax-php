# solax-php

PHP scraper for Solax PV Cloud


To make it work with persistent db storage:
- create a db (tested on PG)
 - impotr db schema from database.sql
-  create a user, and put credentials in the import (example-db.php) and pv_push scripts
- add api-key (not read-only) and site-id to pv_push script

- setup cron to run the import scripts (for example every 15 minutes)
- setup cron to run the push script every 15 minutes (use a 1 minute offset)


