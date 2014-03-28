<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 */
class Test_Controller extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        print "<p>Hello World</p>";
    }

    public function userError() {
        trigger_error("User Error", E_USER_ERROR);
        print "<p>Hello World</p>";
    }


    public function userWarning() {
        trigger_error("User Warning", E_USER_WARNING);
        print "<p>Hello World</p>";
    }

    public function userNotice() {
        trigger_error("User Notice", E_USER_NOTICE);
        print "<p>Hello World</p>";
    }

    public function warning() {
        trigger_error("Warning", E_WARNING);
        print "<p>Hello World</p>";
    }


    public function exception() {
        throw new Exception("Test exception");
    }


    public function exceptionJson() {
        header("Content-Type: application/json");
        throw new Exception("Test exception");
    }


    public function dumpSession() {
        print '<pre>'
            ."Session Name: ". session_name() ."\n\n";
        print_r($_SESSION);
    }


    public function info() {
        phpinfo();
    }


    public function errorReporting() {
        $type = error_reporting();

        $levels = array (
            'E_ERROR' => (($type & E_ERROR) == E_ERROR),
            'E_WARNING' => (($type & E_WARNING) == E_WARNING),
            'E_PARSE' => (($type & E_PARSE) == E_PARSE),
            'E_NOTICE' => (($type & E_NOTICE) == E_NOTICE),
            'E_CORE_ERROR' => (($type & E_CORE_ERROR) == E_CORE_ERROR),
            'E_CORE_WARNING' => (($type & E_CORE_WARNING) == E_CORE_WARNING),
            'E_COMPILE_ERROR' => (($type & E_COMPILE_ERROR) == E_COMPILE_ERROR),
            'E_COMPILE_WARNING' => (($type & E_COMPILE_WARNING) == E_COMPILE_WARNING),
            'E_USER_ERROR' => (($type & E_USER_ERROR) == E_USER_ERROR),
            'E_USER_WARNING' => (($type & E_USER_WARNING) == E_USER_WARNING),
            'E_USER_NOTICE' => (($type & E_USER_NOTICE) == E_USER_NOTICE),
            'E_STRICT' => (defined('E_STRICT') && (($type & E_STRICT) == E_STRICT)),
            'E_RECOVERABLE_ERROR' => (defined('E_RECOVERABLE_ERROR') && (($type & E_RECOVERABLE_ERROR) == E_RECOVERABLE_ERROR)),
            'E_DEPRECATED' => (defined('E_DEPRECATED') && (($type & E_DEPRECATED) == E_DEPRECATED)),
            'E_USER_DEPRECATED' => (defined('E_USER_DEPRECATED') && (($type & E_USER_DEPRECATED) == E_USER_DEPRECATED)),
            'E_ALL' => (($type & E_ALL) == E_ALL),
        );

        print '<pre>';
        print_r ($levels);
    }


    /**
     * Taken from example #4 on http://php.net/password_hash
     */
    public function passwordCost() {
        /**
         * This code will benchmark your server to determine how high of a cost you can
         * afford. You want to set the highest cost that you can without slowing down
         * you server too much. 10 is a good baseline, and more is good if your servers
         * are fast enough.
         */
        $timeTarget = 0.2;

        $cost = 9;
        do {
            $cost++;
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
            $end = microtime(true);
        } while (($end - $start) < $timeTarget);

        echo "Appropriate Cost Found: " . $cost . "\n";
    }

}
