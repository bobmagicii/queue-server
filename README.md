# Queue Server

### Features

* Managed async queue with parallel jobs.
* Queue state stored in SQLite and can be resumed.
* Local socket with protocol for queue management.
* Command line queue management.

### Job Types

* Shell Command

### Requirements

* PHP 8.3+
	* json, pdo, pdo-sqlite

## Run Server

Runs the queue picking up where it left off if it had been running before.

* Phar: `$ php queue.phar run`
* Source: `$ php bin/queue.php run`

Force a fresh empty queue.

* Phar: `$ php queue.phar run --fresh`
* Source: `$ php bin/queue.php run --fresh`

## Add Command to Queue

Add a new shell command to the queue. Everything after `cmd` is the command that will be executed by the queue.

* Phar: `$ php queue.phar cmd echo lol`
* Source: `$ php bin/queue.php cmd echo lol`

## Build `queue.phar`

This command will produce `build/queue-<version>.phar` which is the only file that is then needed to run and manage the queue.

* `$ php bin/queue.php phar`
