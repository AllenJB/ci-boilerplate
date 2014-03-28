Controllers
===========

All controller class names must now end in `_Controller` (eg. `Index_Controller extends MY_FrontendController`). This
prevents collisions with library and model class names. The rules for the filenames are unchanged.

A number of standard base controllers are provided to extend upon:

* MY_AjaxController
    * Sets the headers for AJAX output
* MY_CliController
    * For commandline processes, provides argument passing and prevents access from a web browser.
    * Sets up logging in a suitable manner
    * Prevents timeouts
    * Commandline arguments (in traditional unix style (`--[key]=[value]`) are put into the cliArgs property as an
    associative array.
* MY_CronController
    * For cron processes (anything in `application/controllers/_cron/`)
    * See [Crons](crons.md) for further information
* MY_FrontendController
    * For web-based output.
    * See the **Frontend Controllers** section below for further information.
* MY_Controller
    * Base controller extended by all others
    * See the **Base Controller** section below for further information.


Base Controller
---------------

`MY_Controller` is extended by all the other base controllers, so its functionality is available everywhere.

* loadSession()
    * Initialize the session, allowing the session cookie name to be modified from the default if desired.
    * This uses the `NativeSession` library, which is a drop-in replacement for the CodeIgniter Session library. See
    [Libraries](libraries.md) for further information.
* redirect( *url* )
    * Save flash messages and redirect to the specified url. Explicitly calls exit() to ensure that nothing happens
    after the redirect that you don't want to happen.
* addFlashMessage( *msg*, *type* )
    * Add a flash message to be displayed. Flash messages persist across redirects, so these are useful for displaying
    notifications after form posts when we want to ensure the form data isn't accidentally resubmitted.
    * Type can be one of: success, error, warning, info
    * There is a standard template for displaying flash messages in `application/views/templates/`
* loadFlashMessages()
    * Retrieve and clear the stored flash messages
* checkMemoryUsage()
    * Utility function to attempt to detect when we might be about to run out of memory


Frontend Controllers
--------------------

`MY_FrontendController` provides a layout system and utility functions for displaying / indicating menus and breadcrumbs.

* displayLayoutHead( *var* ) / displayLayoutFoot( *vars* )
    * Display the header and footer of the active layout (as specified by the `layout` property).
* addCrumb( *title* , *url* , *icon* )
    * Add a breadcrumb to the current trail
* addCssFile( *url* ) / addJsFile( *url* )
    * Add a CSS or JS file to be added to the current page.
* setActive(Sub/Subsub)Section
    * Set the active section / subsection / sub-subsection to be indicated on the menu
* setSidebar
    * Set the sidebar menu to be displayed
