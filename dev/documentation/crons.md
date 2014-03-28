Crons
=====

A boilerplate crontab is provided in `etc/crontab` - this is usually copied to `/etc/cron.d/` under a suitable name
for the project.

*Note:* While you can put a symlink in `/etc/cron.d` to the `etc/crontab` file, some crond implementations have issues
with this setup and will not automatically reload the file.

Cron controllers live in `application/controllers/_cron/` and should extend `MY_CronController`, which extends
`MY_CliController`.

There are 2 database tables related to crons:

* cron_locks
    * Indicates where a cron is currently running, what its pid is and how long it was / has been running for
* cron_logs
    * Logs every cron job run, when it started, when it finished and a failure message if it failed
    * Can be used to extrapolate information on how long a given cron generally runs for

Crons also log to `application/data/logs/crons/<cron-name>`. These logs are automatically gzipped if the cron uses
per-process log rotation, or rotated via lograte (config in `etc/logrotate`) for crons that use daily rotation.

Configuration
-------------

Crons have a number of settings that can be globally configured in `application/config/crons.php` and can be overridden
on the commandline.

Config file options:

* log_to_disk
    * Should output be logged to disk?
* log_to_console
    * Should output be logged to the console. This is disabled by default on production to reduce spam to the root mail
    account (as any issues are generally already handled by the cost and sent to developers).
* email_on_success
    * Should the cron send a notification email to developers even if it succeeds? By default this is disabled on
    production and notification emails are only sent when a cron fails.

Command-line options:

* verbose
    * Overrides the `log_to_console` option, telling to cron to display logs on the console.
* email *=1/0*
    * Toggle whether or not to always send an email, overriding the `email_on_success` setting.
* help
    * Display usage information (reminder of available options)
* from / to
    * Set the from and to dates for the cron (if it uses date ranges). These can be anything that the DateTime
    constructor accepts.
* all
    * Set the from / to dates to the maximum available values, running the cron for values from "all time".


Date Ranges
-----------

MY_CronController has a built-in system for handling date ranges, which is useful for things like stats building crons.
There are 2 configurable values: default date and earliest date. The earliest date sets the earliest date that should
ever be in the "from date" value (eg. the date the site or feature went live). The default date sets the default from
date (the global default is 2 days, but you may want to override this depending on the length of time that the data
you're looking at can fluctuate for).

The from and to values can be overridden from the commandline (which can be useful if you want to have a daily cron
that looks at the past 2 days data, then a weekly cron that recalculates data for the past month).

Call `getDateRangeStart()` and `getDateRangeEnd()` to retrieve the from / to dates. By default these methods modify
the time on the dates to '00:00:00' and '23:59:59' respectively, but this behaviour can be disabled using the boolean
parameter.


Cron Flow
---------

In general all crons should flow in the following way:

1. setCronName( *name* )
    * Sets the name of the cron (used for logs and locks)
2. checkIsCronRunning()
    * Check if the cron is already running. If it is, the cron will immediately fail to prevent collisions.
3. setCronIsRunning()
    * Lock the cron and log that fact that it has started running
4. Cron logic
    * The cron performs whatever task(s) it wants to do.
    * If at any point the cron wants to abort (fail), it should call `setCronFailed(msg)` followed by
    `setCronIsFinished()` before calling exit()
5. setCronIsFinished()
    * Unlocks the cron and logs the fact that it has completed
