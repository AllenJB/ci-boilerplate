Database
========

Numerous improvements have been made to the CodeIgniter database handling and models. It should also be noted that
all of the database drivers except for mysqli have been stripped from the code - this is the only driver that has been
modified and tested.


Core Modifications
------------------

* reset()
    * New method belonging to `CI_DB_active_record` that resets the active query builder state (ie. clears all where()
    and other values). This allows us to abort building a query part-way through without affecting subsequent queries.
* Query data
    * CodeIgniter no longer stores data on every SQL query run. It only stores the SQL for the last query.
* _fetch_object() / _fetch_assoc()
    * The `_fetch_object()` and `_fetch_assoc()` functions have been exposed, allowing for large data sets to be
    easily iterated over without unnecessarily consuming massive amounts of memory (CodeIgniters default
    functionality always pulls the entire result set into a PHP array before iterating over it).
* Fix Silent Errors
    * The `where_in` and `where_not_in` query builder functions will now raise an error if passed `NULL` values.


Models
------

Two standard core models are now provided: `MY_BasicModel` is the most basic, with only minor additional functionality,
while `MY_Model` provides a standard model that provides basic CRUD functionality which can be extended as desired.

Basic model functionality:

* lastError()
    * Returns the last database error
* lastQuery()
    * Returns the last SQL query that was run
* reconnect()
    * Reconnect to the database server, if we've been disconnected
* returnError( *msg* )
    * Provides a standard error handling method (by default this triggers a user error and resets the active query
    builder). Provides a default `NULL` return value (no data returned)
* escapeArray( *array* )
    * Recursively iterates over an array of values, escaping each value. Maintains key associations.


For documentation on `MY_Model` functionality, see the code documentation. Essentially you only need to set the `$table`
and `$keyField` parameters for basic CRUD functionality.