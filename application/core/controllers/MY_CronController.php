<?php

/**
 * @property CI_Email $email
 * @property Crons $crons
 */
class MY_CronController extends MY_CliController {

    protected $controllerType = 'cron';

    protected $lockId = NULL;

    protected $logToMaster = TRUE;

    /**
     * @var NULL|object
     */
    protected $lastRun = NULL;

    protected $emailOnSuccess = TRUE;

    protected $cronFailed = FALSE;

    protected $logCrons = NULL;

    protected $cronName = 'unnamed_cron';

    protected $timeStart = NULL;

    protected $dateRangeAll = FALSE;

    /**
     * @var DateTime
     */
    protected $dtDateRangeEarliest = NULL;

    /**
     * @var DateTime
     */
    protected $dtDateRangeStart = NULL;

    protected $defaultDateStart = "-2 days";

    /**
     * @var DateTime
     */
    protected $dtDateRangeEnd = NULL;

    protected $truncate = FALSE;

    protected $failureMessage = NULL;

    protected $logRotation = 'process';

    protected $logDir = NULL;


    public function __construct() {
        parent::__construct();

        $this->logDir = realpath(DATA_DIR .'/logs') .'/crons/';

        $this->setDateRangeEarliest('2014-01-01 00:00:00');

        $this->load->library('email');

        $this->config->load('crons', TRUE);
        $this->logger->setLogToConsole( $this->config->item('log_to_console', 'crons') );
        $this->logger->setLogToDisk( $this->config->item('log_to_disk', 'crons') );
        $this->emailOnSuccess = $this->config->item('email_on_success', 'crons');
        if (array_key_exists('RUN_FROM_CRONTAB', $_SERVER) && ($_SERVER['RUN_FROM_CRONTAB'])) {
            $this->logger->setLogToConsole(FALSE);
        }

        // We've messed with the logger configuration, so re-parse the cli arguments
        $this->parseCliArgs();

        if ($this->logRotation == 'daily') {
            $this->logger->setFileDateFormat('Y-m-d');
        } else {
            $this->logger->setFileDateFormat('Y-m-d_His');
        }

        $this->load->model('crons');
    }


    protected function parseCliArgs() {
        parent::parseCliArgs();

        if (is_array($this->cliArgs)) {
            if (array_key_exists('from', $this->cliArgs) && strlen($this->cliArgs['from'])) {
                $this->setDateRangeStart($this->cliArgs['from']);
            }
            if (array_key_exists('to', $this->cliArgs) && strlen($this->cliArgs['to'])) {
                $this->setDateRangeEnd($this->cliArgs['to']);
            }
            if (array_key_exists('all', $this->cliArgs)) {
                $this->setDateRangeStart('all');
            }
            if (array_key_exists('email', $this->cliArgs)) {
                $this->emailOnSuccess = (bool) $this->cliArgs['email'];
            }
        }
    }


    protected function printUsage() {
        print "Cron Options:"
            ."\n--from=\"<date>\"        Date range start"
            ."\n--to=\"<date>\"          Date range end (default = today)"
            ."\n--all                    Maximum date range"
            ."\n--verbose                Log to console (overriding other settings)"
            ."\n--help                   This screen"
            ."\n--email=<1/0>            Always send email, even on success (overriding other settings)"
            ."\n"
            ."\nDates can be anything understood by DateTime::__construct (eg. \"-3 month\")."
            ."\nNOTE: Not all crons obey date range (notably CoReg and DCR)"
            ."\n";
        exit();
    }


    /**
     * Set the cron name to be used by log files and in the last run table
     * @param $name
     */
    protected function setCronName($name) {
        if (!preg_match('/^[a-zA-Z0-9_\-\ ]+$/i', $name)) {
            $msg = "Invalid cron name: {$name}";
            mail(DEVELOPER_EMAILS, PROJECT_NAME .": Cron has invalid name: {$name}", $msg);
            die("Invalid cron name: {$name}");
        }
        $this->cronName = $name;
        $this->logger->setFilePart($this->cronName);
        $this->logger->setDirectory($this->logDir . $this->cronName .'/');

        $argv = (array_key_exists('argv', $_SERVER) ? join(' ', $_SERVER['argv']) : '');
        $this->logProcess("Set cron name: {$name} :: Args: {$argv}");
    }


    /**
     * Do entries get logged to the log_crons table in the master DB?
     *
     * This allows us to disable the feature for crons that run highly frequently and would flood the table uselessly
     * (eg. email crons on PO)
     * @param bool $enabled
     */
    protected function setLogToMaster($enabled = TRUE) {
        $this->logToMaster = $enabled;
    }


    /**
     * Set the failed status of the cron job.
     * @param string $msg Failure message
     */
    protected function setCronFailed($msg) {
        $this->cronFailed = TRUE;
        $this->failureMessage = $msg;

        $this->logProcess("Cron FAILED: {$this->cronName}  reason: {$msg}");
    }


    /**
     * Check whether the cron is already running.
     * If it is, we abort.
     * If not, we set the lastRun data
     */
    protected function checkIsCronRunning () {
        $Running = $this->crons->isLocked($this->cronName);

        $this->lastRun = $this->crons->getByCron($this->cronName);

        if ($Running === TRUE){
            $this->logger->log("=== ABORTED :: Already running ({$this->cronName})");
            $this->setCronFailed("Already Running");
            $this->closeLog(TRUE);
            exit();
        }

    }

    /**
     * Update the crons table to say the cron is now running
     */
    protected function setCronIsRunning () {
        $this->logger->log("=== START :: {$this->cronName}");
        $this->logger->log("Environment: ". ENVIRONMENT);
        $this->logger->log("Email on Success: ". ($this->emailOnSuccess ? 'TRUE' : 'FALSE'));

        $this->lockId = $this->crons->lock($this->cronName, $this->logRotation);

        $this->logProcess("Cron START: {$this->cronName}");

        $this->timeStart = time();
    }


    /**
     * Update the cron table to say the cron has finished
     */
    protected function setCronIsFinished () {
        $elapsed = time() - $this->timeStart;
        $this->logger->log("=== COMPLETED :: {$this->cronName} :: Time: {$elapsed} secs");

        $this->logger->logMemoryUsage();
        $this->crons->reconnect();
        if ($this->lockId > 0) {
            $affected = $this->crons->unlock($this->lockId);
            if ($affected < 1) {
                $this->logger->log("ERROR: Failed to update lock row for id: {$this->lockId}");
                $this->logger->log("Last SQL query: {$this->crons->lastQuery()}");
                $this->logger->log("Last SQL error: {$this->crons->lastError()}");
            }
        }

        $status = 'SUCCESS';
        if ($this->cronFailed) {
            $status = 'FAILED (property)';
            if (strlen($this->failureMessage)) {
                $status .= " ". $this->failureMessage;
            }
        }
        $this->logger->log("Cron status: ". $status);
        $this->logProcess("Cron END: {$this->cronName}  status: {$status}");

        $this->closeLog();
    }


    /**
     * Handle "closing" the log file.
     * We compress the log file to save disk space.
     * We email the log file to developers (always if the cron failed, or optionally on success)
     */
    private function closeLog() {
        $this->masterLog();

        $compressedFile = NULL;
        $logFileName = $this->logger->getFile();
        if ($this->logRotation == 'process') {
            $compressedFile = $this->logger->compress();
            $logFileName = $compressedFile;
        }

        if (($this->cronFailed) || $this->emailOnSuccess) {
            $this->load->library('email');
            $this->email->clear();

            $msg = "";

            $subject = "{$this->cronName} - ". ($this->cronFailed ? 'FAILED' : ''). ENVIRONMENT ." - "
                . PROJECT_NAME ." Cron";
            $msg .= "Log File: {$logFileName}\n"
                ."Cron Name: {$this->cronName}\n"
                ."Status: ". ($this->cronFailed ? 'FAILED' : 'Success') ."\n"
                ."Failure Message: ". ($this->failureMessage !== NULL ? $this->failureMessage : '') ."\n"
                ."\n\nLast Run: \n". print_r($this->lastRun, TRUE) ."\n"
                ."\n--- EOM ---\n";

            if ($compressedFile !== NULL) {
                $this->email->attach($compressedFile);
            }

            $this->email
                ->to(DEVELOPER_EMAILS)
                ->subject($subject)
                ->set_priority(($this->cronFailed ? 1 : 3))
                ->message($msg)
                ->send();
            ;
        }
    }


    /**
     * Record entries in a central table for easier evaluation
     */
    protected function masterLog() {
        if (!$this->logToMaster) {
            return;
        }

        $lastRun = $this->crons->getByCron($this->cronName);

        if (!is_object($lastRun)) {
            return;
        }
        $record = array (
            'cron' => $lastRun->cron,
            'dt_started' => $lastRun->dt_started,
            'dt_ended' => $lastRun->dt_ended,
            'failed' => $this->cronFailed,
            'message' => $this->failureMessage,
        );

        $this->crons->addLog($record);
    }


    private function logProcess($msg) {
        if ($this->logCrons === NULL) {
            $logdir = realpath(DATA_DIR .'/logs') .'/crons/';
            if (!file_exists($logdir)) {
                mkdir($logdir, 0777, TRUE);
            }
            $filename = $logdir . date('Y-m-d') ."_crons.log";
            $this->logCrons = $filename;
        }

        $pid = getmypid();
        $msg = date('Y-m-d H:i:s') ." [{$pid}] $msg\n";

        file_put_contents($this->logCrons, $msg, FILE_APPEND);
    }


    private function initDates() {
        if ($this->dtDateRangeEnd === NULL) {
            $this->setDateRangeEnd('now');
        }

        if ($this->dtDateRangeStart === NULL) {
            $this->setDateRangeStart($this->defaultDateStart);
        }
    }


    protected function setDateRangeEarliest($date) {
        if (is_object($date) && ($date instanceof DateTime)) {
            $this->dtDateRangeEarliest = $date;
        } else {
            $this->dtDateRangeEarliest = new DateTime($date);
        }
        $this->dtDateRangeEarliest->setTime(0, 0, 0);
    }


    protected function setDateRangeStart($date) {
        if (strtolower($date) == 'all') {
            // Set to an early date that will always be before "earliest date"
            // This is then "normalized" to earliest date when we fetch it
            $this->dtDateRangeStart = new DateTime("2000-01-01");
            $this->dateRangeAll = TRUE;
            return;
        }

        if (is_object($date) && ($date instanceof DateTime)) {
            $this->dtDateRangeStart = $date;
        } else {
            $this->dtDateRangeStart = new DateTime($date);
        }
    }

    protected function setDateRangeEnd($date) {
        if ($date == 'all') {
            $this->dtDateRangeEnd = new DateTime();
            return;
        }

        if (is_object($date) && ($date instanceof DateTime)) {
            $this->dtDateRangeEnd = clone $date;
        } else {
            $this->dtDateRangeEnd = new DateTime($date);
        }
    }


    /**
     * @param bool $modifyTime Set the time to midnight (00:00:00)?
     * @return DateTime
     */
    protected function getDateRangeStart($modifyTime = TRUE) {
        $this->initDates();

        $retval = clone $this->dtDateRangeStart;
        if ($retval < $this->dtDateRangeEarliest) {
            $retval = clone $this->dtDateRangeEarliest;
        }
        if ($modifyTime) {
            $retval->setTime(0, 0, 0);
        }

        return $retval;
    }


    /**
     * Were we asked to process all data?
     * @return bool
     */
    protected function isDateRangeAll() {
        return $this->dateRangeAll;
    }


    /**
     * @param bool $modifyTime Set the time to midnight (23:59:59)?
     * @return DateTime
     */
    protected function getDateRangeEnd($modifyTime = TRUE) {
        $this->initDates();

        $retval = clone $this->dtDateRangeEnd;
        if ($retval < $this->dtDateRangeStart) {
            $retval = clone $this->dtDateRangeEarliest;
        }
        if ($retval < $this->dtDateRangeEarliest) {
            $retval = clone $this->dtDateRangeEarliest;
        }
        if ($modifyTime) {
            $retval->setTime(23, 59, 59);
        }

        return $retval;
    }


    /**
     * @return DateTime
     */
    protected function getDateRangeEarliest() {
        return clone $this->dtDateRangeEarliest;
    }


    /**
     * Are we truncating the entire table (mostly used for stats generators)
     * @param bool $bool
     */
    protected function setTruncate($bool = TRUE) {
        $this->truncate = $bool;
    }


    /**
     * Are we truncating the entire table (mostly used for stats generators)
     * @return bool
     */
    protected function getTruncate() {
        return $this->truncate;
    }

}
