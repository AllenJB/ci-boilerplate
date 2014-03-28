Error Handling
==============

In addition to the standard CodeIgniter logging, a number of modifications / extensions have been made to provide
improved error handling.

The standard method of error reporting is to email errors to developers (as specified in `DEVELOPER_EMAILS`).

PHP Errors
----------

PHP errors are handled by `application/core/MY_Exceptions.php`. Errors that are considered non-fatal are completely
hidden from end-users when running in a *production* `ENVIRONMENT`, but displayed in-line when in *development*. Errors
that are considered fatal are set up to display a suitable page in a *production* `ENVIRONMENT`.

CodeIgnitier's core error handler that calls `CI_Exceptions` (and thus `MY_Exceptions`) has been modified so that
strict errors are not completely ignored and to make `error_get_last()` work correctly.


Fatal Errors
------------

A shutdown error handler is setup in `public/index.php` to catch shutdown errors where possible. This is aided by a
cron (in `application/controllers/_cron/misc.php`) that checks the error log file and emails those errors to
developers.


Uncaught Exceptions
-------------------

An uncaught exception handler is set up in `public/index.php` and handled by `show_exception()` in
`application/core/MY_Exceptions.php`.


404 Pages
---------

CodeIgniter's 404 handling is overridden by `application/core/MY_Exceptions.php` but does not email developers.


Other Errors
------------

Special handling has been put in place to separate out database errors and 'Disallowed URI characters' errors to allow
for easier filtering of emails and to attempt to hilight the problematic characters.

Additionally the CodeIgniter `CI_Input` code has been modified so that 'Disallowed Key Characters' throws an exception
instead of `die()`'ing. This allows this error case to be caught and dealt with (or atleast develoeprs notified).

