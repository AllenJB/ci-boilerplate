Libraries
=========

* Date
    * Library for handling date display. Allows you to quickly change the format dates are displayed in across the
    project.
* File
    * File reading/writing utility functions.
* Logger
    * Logging library with support for simultaneous console and file logging.
* NativeSession
    * Drop-in replacement for CodeIgniter sessions that uses native PHP sessions.
    * This avoids storing excessive amounts of data on the client, and issues when session cookies hit 4K in size.
* Notifications
    * Library for quickly dispatching email notifications to developers in a similar format to errors.
* Paginator
    * Pagination library
* Profile
    * Code timing library that allows the setting of multiple marks and calculating the differences between them, as
    well as returning a dump of all stored marks.
* Rest
    * Library that wraps CURL for interacting with HTTP services


Javascript
----------

The `public/js/global.js` file includes some utility functions:

* html_escape
    * Escape a string for output as HTML
* real_typeof
    * A replacement for typeof that returns a better representation of the value type
