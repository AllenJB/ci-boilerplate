<?php

// Save log file to disk
$config['log_to_disk'] = TRUE;

// Log output to the console
// We disable this on live to avoid Tim receiving emails he doesn't care about
$config['log_to_console'] = FALSE;

// Email even when the cron succeeds (always emails on failure)
// I'm setting this to on initially, and then will disable it later
$config['email_on_success'] = FALSE;
