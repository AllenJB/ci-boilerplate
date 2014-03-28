<?php

/**
 * Base class for all commandline scripts / processes / crons
 *
 * @property Logger $logger
 */
class MY_CliController extends MY_Controller {

    /**
     * @var array Arguments passed from the commandline
     */
    protected $cliArgs = array();


    public function __construct() {
        parent::__construct();

        ini_set("max_execution_time", "0");
        set_time_limit(0);

        if (! $this->input->is_cli_request() ) {
            header("HTTP/1.1 403 Forbidden");
            die();
        }

        $this->load->library('logger');
        if (ENVIRONMENT == 'development') {
            $this->logger->setLogToConsole();
        }
        $this->getArgs();
        $this->parseCliArgs();
    }


    protected function getArgs() {
        // I would use getopt() here, but it seems to be broken under CodeIgniter for some reason
        global $argv;
        $cliArgs = array();
        foreach ($argv as $arg) {
            if (strpos($arg, '-') !== 0) {
                continue;
            }

            $parts = explode('=', $arg, 2);
            $key = $parts[0];
            $value = NULL;
            if (count($parts) > 1) {
                $value = $parts[1];
            }

            if (strpos($key, '--') === 0) {
                $key = substr($key, 2);
            } else if (strpos($key, '-') === 0) {
                $key = substr($key, 1);
            }

            $cliArgs[$key] = $value;
        }

        $this->cliArgs = $cliArgs;
    }


    protected function parseCliArgs() {
        if (is_array($this->cliArgs)) {
            if (array_key_exists('verbose', $this->cliArgs)) {
                $this->logger->setLogToConsole();
            }
            if (array_key_exists('help', $this->cliArgs)) {
                $this->printUsage();
            }
        }
    }


    protected function printUsage() {
        die("Not implemented");
    }

}
