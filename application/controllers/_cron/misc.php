<?php if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 */
class Misc_Controller extends MY_CronController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function test()
    {
        $this->setCronName('test');
        $this->checkIsCronRunning();
        $this->setCronIsRunning();

        for ($i = 5; $i > 0; $i--) {
            $this->logger->logProgress("Sleeping for {$i} seconds...  " . str_repeat("*", $i));
            sleep(1);
        }
        $this->logger->logProgressEnd();
        $this->logger->log("Countdown finished!");

        $this->setCronIsFinished();
    }


    public function testFailure()
    {
        $this->setCronName('test_failure');
        $this->checkIsCronRunning();
        $this->setCronIsRunning();

        for ($i = 5; $i > 0; $i--) {
            $this->logger->logProgress("Sleeping for {$i} seconds...  " . str_repeat("*", $i));
            sleep(1);
        }
        $this->logger->logProgressEnd();
        $this->logger->log("Countdown finished!");

        $this->setCronIsFinished(true);
    }


    /**
     * Quick and dirty garbage collection for the log_crons in the master DB to avoid it getting overly full
     */
    public function gc()
    {
        $this->crons->gcLogs();
    }


    /**
     * Check the php_errors.log for anything we wouldn't have been otherwise notified about (eg. fatal errors)
     */
    public function notifyLoggedErrors()
    {
        $this->setCronName('notify_logged_errors');

        if (! file_exists(LOG_FILE_PHP)) {
            $this->logger->log("PHP Error log does not exist: " . LOG_FILE_PHP);
            return;
        }

        // Get the last logged error time from the data file, if it exists
        $lastLog = null;
        $dataFile = DATA_DIR . '/cron_php_errors.dat';
        if (file_exists($dataFile)) {
            $time = trim(file_get_contents($dataFile));
            $lastLog = new DateTime($time);
        }

        $fh = fopen(LOG_FILE_PHP, FOPEN_READ);
        if ($fh === false) {
            $this->logger->log("Unable to open PHP error log");
            return;
        }

        $collecting = false;
        if (! is_object($lastLog)) {
            $collecting = true;
        }

        $logs = array();
        $lastDate = null;
        while (false !== ($line = fgets($fh))) {
            $line = trim($line);
            if (strlen($line) < 1) {
                continue;
            }

            $datedLine = false;
            $date = null;
            $type = null;
            if (preg_match('/^\[(?P<date>[0-9A-Za-z\ \-\:]+)\] (?P<type>[0-9A-Za-z\ ]+): /', $line, $matches)) {
                $datedLine = true;
                $date = new DateTime($matches['date']);
                if (is_object($date)) {
                    $lastDate = $date;
                    $datedLine = true;
                    $type = $matches['type'];
                }

                if ($lastDate > $lastLog) {
                    $collecting = true;
                }
            }

            if (strlen($type)) {
                $ignoreTypes = array('PHP Notice', 'User Notice', 'User Warning', 'User Error');
                $ignore = false;
                foreach ($ignoreTypes as $ignoreType) {
                    if (strpos($type, $ignoreType) !== false) {
                        $ignore = true;
                        break;
                    }
                }

                if ($ignore) {
                    // Don't collect anything until the next dated line
                    $collecting = false;
                    continue;
                }
            }

            if ($collecting) {
                $logs[] = $line;
            }
        }


        if (count($logs)) {
            $email = "The following errors have been logged:\n";
            $email .= join("\n", $logs);
            $email .= "\n\n--- EOM ---\n";

            mail(DEVELOPER_EMAILS, PROJECT_NAME . " Logged Errors", $email);


            file_put_contents($dataFile, $lastDate->format('Y-m-d H:i:s'));
        }

        $this->logger->log("Done");
    }
}
