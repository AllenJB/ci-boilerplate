Constants
=========

* DATA_DIR
    * Path to the non-public data directory (where all stored files / logs / etc should be written to when they don't
    need to be directly accesible via the browser).
    * Set up in `config/constants.php`
* DATA_DIR_PUBLIC
    * Path to the public data directory (where all stored files should be written to when they do need to be directly
    accessible via the browser)
    * Set up in `config/constants.php`
* DATA_DIR_PUBLIC_URL
    * Url path from the domain root to the public data directory
    * Set up in `config/constants.php`
* DEVELOPER_EMAILS
    * List of email addresses to which notification / error emails are sent
    * Set up in `public/index.php` (shutdown_handler() fallback) and `config/constants.php`
* ENVIRONMENT
    * Specifies the environment the application is operating in
    * Set up in `public/index.php`
* PROJECT_NAME
    * Human name for the project - mostly used by default in email subjects for easier filtering
    * Set up in `public/index.php`
* PROJECT_ROOT
    * Specifies the project root directory
    * Set up in `public/index.php`
* URL_PROTOCOL
    * URL Protcol (http or https)
    * Set up in `config/constants.php`
