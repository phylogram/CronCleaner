# CronCleaner
Sometimes your Cronjobs in Wordpress get mixed up. Clean up!


## Usage
```php
require './vendor/autoload.php';
use \phylogram\CronScheduleCleaning\CronScheduleCleaner;
$cleaner = new CronScheduleCleaner('/your_action/', true); # throw error on first event, that could not be unscheduled by wordpress
echo $cleaner;
```
```
Cleaner Status: true

Cron Manager for hook your_action with 6 crons and 5 to delete
	Will be preserver:	Cron your_action at 2020-12-19T00:30:00+00:00
	Will be deleted: Cron your_action at 2020-08-24T00:30:00+00:00
	Will be deleted: Cron your_action at 2020-09-30T00:30:00+00:00
	Will be deleted: Cron your_action at 2020-09-30T00:30:00+00:00
	Will be deleted: Cron your_action at 2020-09-30T00:30:00+00:00
	Will be deleted: Cron your_action at 2020-12-18T00:30:00+00:00

```