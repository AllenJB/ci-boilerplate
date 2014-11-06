<?php  if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ', 'rb');
define('FOPEN_READ_WRITE', 'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE', 'ab');
define('FOPEN_READ_WRITE_CREATE', 'a+b');
define('FOPEN_WRITE_CREATE_STRICT', 'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

if (! defined('DEVELOPER_EMAILS')) {
    define('DEVELOPER_EMAILS', join(
        ',',
        array(
            "user@example.com",
        )
    ));
}

define('DATA_DIR', realpath(APPPATH . '/data/') . '/');
define('DATA_DIR_PUBLIC', realpath(FCPATH . '/data/') . '/');
define('DATA_DIR_PUBLIC_URL', '/data/');

if (! defined('ROOT_DOMAIN')) {
    define('ROOT_DOMAIN', 'example.com');
}
define('URL_PROTOCOL', ((! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http'));


session_save_path(DATA_DIR . 'session/');
define('LOG_FILE_PHP', DATA_DIR . 'logs/' . date('Ymd') . '_php_errors.log');

ini_set('error_log', LOG_FILE_PHP);

/* End of file constants.php */
/* Location: ./application/config/constants.php */