<?php  if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$devEmails = array(
    "user@example.com",
);
define('DEVELOPER_EMAILS', join(',', $devEmails));

require_once(dirname(__FILE__) . '/../constants.php');
