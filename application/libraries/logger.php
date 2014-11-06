<?php

class Logger
{

    /**
     * @var array All available log levels. The MUST be in order.
     */
    protected $levels = array('debug', 'info', 'warn', 'error', 'fatal');

    /**
     * @var int The current logging level
     */
    protected $level = 0;

    /**
     * @var bool Log to the console?
     */
    protected $logToConsole = true;

    /**
     * @var bool Log to disk?
     */
    protected $logToDisk = true;

    /**
     * @var null|string Directory to store logs in
     */
    protected $directory = null;

    /**
     * @var string String to append to the filename
     */
    protected $filePart = '';

    /**
     * @var null|string Full filename, including path, of the file
     */
    protected $file = null;

    /**
     * @var string Prefix to append to every log line
     */
    protected $prefix = '';

    /**
     * @var string Date format used in the filename
     */
    protected $fileDateFormat = 'Y-m-d_His';

    /**
     * @var string Date format used in log lines
     */
    protected $lineDateFormat = 'Y-m-d H:i:s';


    public function __construct()
    {
        // Flip levels, so that $this->levels[$level] gives us a number
        $this->levels = array_flip($this->levels);
    }


    public function setFilePart($part)
    {
        $this->filePart = $part;
        if (strlen($this->directory)) {
            $this->updateFilename();
        }
    }


    protected function updateFilename()
    {
        $file = '';

        if (strlen($this->fileDateFormat) > 0) {
            $file .= date($this->fileDateFormat);
            if (strlen($this->filePart) > 0) {
                $file .= '_';
            }
        }

        if (strlen($this->filePart) > 0) {
            $file .= $this->filePart;
        } else {
            if (strlen($this->fileDateFormat) < 1) {
                $file .= 'current';
            }
        }

        $this->file = $this->directory . $file . '.log';
    }


    public function setDirectory($dir)
    {
        $this->directory = $dir;
        if (! (file_exists($this->directory) && is_dir($this->directory))) {
            $this->log("Creating log directory: {$this->directory}", 'info');
            mkdir($this->directory, 0775, true);
        }
        $this->updateFilename();
        if (! defined('ERROR_HANDLER_LOG')) {
            define('ERROR_HANDLER_LOG', $this->file);
        }
    }


    public function setLevel($level)
    {
        $this->level = $this->levels[$level];

        $levels = array_flip($this->levels);
        $this->log("Log Level set to: " . $levels[$this->level], 'info');
    }


    public function setPrefix($string = '')
    {
        if ((strlen($string) > 0) && (substr($string, -1) != ' ')) {
            $string .= ' ';
        }
        $this->prefix = $string;
    }


    public function setFileDateFormat($format)
    {
        $this->fileDateFormat = $format;
        if (strlen($this->directory)) {
            $this->updateFilename();
        }
    }


    public function setLineDateFormat($format)
    {
        $this->lineDateFormat = $format;
    }


    public function init()
    {
        $this->log(str_repeat('-', 80), 'info');
        $this->log("Logging to: {$this->file}", 'info');
    }


    public function log($msg, $level = 'info', $diskOnly = false)
    {
        $logLevel = $this->levels[$level];
        if ($this->level > $logLevel) {
            return;
        }

        $level = strtoupper($level);
        $line = $this->date() . " {$level} {$this->prefix}{$msg} \n";
        if ($this->logToDisk && strlen($this->file)) {
            if (file_exists($this->file) && (! is_writable($this->file))) {
                $this->logToDisk = false;
                trigger_error("Unable to write to log file - Log to disk has been FORCE DISABLED", E_USER_NOTICE);
            } else {
                file_put_contents($this->file, $line, FILE_APPEND);
            }
        }

        if ($diskOnly) {
            return;
        }

        if ($this->logToConsole) {
            print $line;
        }
    }


    /**
     * Create a date/time stamp string in a specified format, using a method that makes resolutions lower than seconds
     * work.
     *
     * We also have to make sure that there is a decimal point with numbers after it, otherwise the 'create from
     * format' fails.
     *
     * @return bool|string
     */
    protected function date()
    {
        $ts = number_format(microtime(true), 6, '.', '');
        $dt = date_create_from_format("U.u", $ts);
        if (! is_object($dt)) {
            trigger_error(
                "Failed to created timestamp for {$ts}: " . print_r(DateTime::getLastErrors(), true),
                E_USER_NOTICE
            );
            return date($this->lineDateFormat);
        }
        return $dt->format($this->lineDateFormat);
    }


    /**
     * Log a progress message. This overwrites the previous contents of the current line on the console.
     *
     * @param String $msg Message to log
     * @param string $level Log level
     */
    public function logProgress($msg, $level = 'info')
    {
        $logLevel = $this->levels[$level];
        if (! array_key_exists($level, $this->levels)) {
            trigger_error("Invalid log level specified: {$level}", E_USER_NOTICE);
            $logLevel = $this->levels[$level];
        }
        if ($this->level > $logLevel) {
            return;
        }
        $this->log($msg, $level, true);

        if ($this->logToConsole) {
            $level = strtoupper($level);
            $line = "\r\x1B[K" . $this->date() . " {$level} {$this->prefix}{$msg}";
            print $line;
        }
    }


    /**
     * End a section of progress log messages (move to next console line)
     */
    public function logProgressEnd()
    {
        if ($this->logToConsole) {
            print "\n";
        }
    }


    protected function bytes_to_human($bytes)
    {
        $human = null;
        if ($bytes < 1024) {
            $human = number_format($bytes, 0) . ' bytes';
        } else if ($bytes < 1024 * 1024) {
            $human = number_format(($bytes / 1024), 1) . ' KB';
        } else {
            $human = number_format(($bytes / (1024 * 1024)), 1) . ' MB';
        }
        return $human;
    }


    public function logMemoryUsage()
    {

        $memUsageString = "";
        if (function_exists('memory_get_usage')) {
            $mem = memory_get_usage();
            $mem_text = $this->bytes_to_human($mem);

            $rmem = memory_get_usage(true);
            $rmem_text = $this->bytes_to_human($rmem);
            $memUsageString .= "Memory Usage: " . $mem_text . " / Real: " . $rmem_text . " :: ";
            unset ($mem, $mem_text, $rmem, $rmem_text);
        }
        if (function_exists('memory_get_peak_usage')) {
            $mem = $this->bytes_to_human(memory_get_peak_usage());
            $rmem = $this->bytes_to_human(memory_get_peak_usage(true));
            $memUsageString .= "Peak Mem Usage: " . $mem . " / Real: " . $rmem;
        }
        if (strlen($memUsageString)) {
            $this->log($memUsageString);
        }
    }


    /**
     * Is the specified level the same as or higher than 'error' (ie. does it include 'fatal' or anything else we come
     * up with)
     */
    public function isErrorLevel($level)
    {
        $logLevel = $this->levels[$level];
        if ($this->level > $logLevel) {
            trigger_error("Invalid log level: {$level}", E_USER_NOTICE);
            return false;
        }

        return ($logLevel >= $this->levels['error']);
    }


    public function compress()
    {
        if ($this->file === null) {
            return null;
        }

        exec("gzip -fq \"{$this->file}\"");
        return $this->file . '.gz';
    }


    public function getFile()
    {
        return $this->file;
    }


    public function setLogToConsole($enabled = true)
    {
        $this->logToConsole = $enabled;
    }


    public function setLogToDisk($enabled = true)
    {
        $this->logToDisk = $enabled;
    }
}
