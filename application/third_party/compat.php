<?php

// This file contains code used to implement backwards compatibility for newer methods which may not exist
// in the running PHP version yet.

/**
 * http://php.net/json_last_error_msg
 * @version 5.5.0
 */
if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg() {
        static $errors = array(
            JSON_ERROR_NONE => NULL,
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        );
        // Note: Errors introduced in PHP 5.5.0 are intentionally ommitted as the built-in version of this method
        // will be used instead on these PHP versions
        $error = json_last_error();
        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
}
