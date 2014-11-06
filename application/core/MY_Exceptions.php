<?php if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class MY_Exceptions extends CI_Exceptions
{

    protected $projectName = 'Boilerplate';

    protected $outputFormat = 'html';

    static $errors = array();


    function __construct()
    {
        parent::__construct();
        if (defined('PROJECT_NAME')) {
            $this->projectName = PROJECT_NAME;
        }

        $this->setOutputFormat();
    }


    protected function setOutputFormat()
    {
        if ($this->isCliRequest()) {
            $this->outputFormat = 'text';
            return;
        }
        if (array_key_exists('PATH_INFO', $_SERVER) && (strpos($_SERVER['PATH_INFO'], '/api/') === 0)) {
            $this->outputFormat = 'json';
            return;
        }

        $headers = headers_list();
        if (is_array($headers)) {
            foreach ($headers as $header) {
                $headerParts = explode(':', $header, 2);
                if (count($headerParts) < 2) {
                    continue;
                }

                $key = $headerParts[0];
                $value = trim($headerParts[1]);

                if (($key == 'Content-Type') && ($value == 'application/json')) {
                    $this->outputFormat = 'json';
                }
            }
        }
    }


    protected function isCliRequest()
    {
        return (php_sapi_name() === 'cli' OR defined('STDIN'));
    }


    /**
     * 404 Page Not Found Handler
     *
     * @param string $page the page
     * @param bool $log_error log error? yes/no
     * @return void
     */
    public function show_404($page = '', $log_error = true)
    {
        if ($this->outputFormat == 'text') {
            print "ERROR: 404: The specified URL does not exist: {$page}\n";
            exit(1);
        }

        header('HTTP/1.0 404 Not Found');

        if ($this->outputFormat == 'json') {
            $response = array(
                'error' => 'not_found',
                'error_message' => 'The requested end point was not found: ' . $page,
            );
            print json_encode($response);
            exit();
        }

        // By default we log this, but allow a dev to skip it
        if ($log_error) {
            log_message('error', '404 Page Not Found --> ' . $page);
            log_message('debug', '_SERVER: ' . print_r($_SERVER, true));
        }

        include(APPPATH . 'errors/error_404.php');
        exit();
    }


    /**
     * General Error Page
     *
     * This function takes an error message as input (either as a string or an array) and displays
     * it using the specified template.
     *
     * @param    string $heading the heading
     * @param    string $message the message
     * @param    string $template the template name
     * @param    int $status_code the status code
     * @return    string Output to display
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        // Prevent recursion
        if (defined('RECURSIVE_SHOW_ERROR')) {
            return '';
        }
        define('RECURSIVE_SHOW_ERROR', 1);

        if (! (($this->outputFormat == 'text') || headers_sent())) {
            set_status_header($status_code);
        }

        $response = array(
            'status' => 'error',
            'error' => 'error',
            'error_heading' => $heading,
            'error_message' => (is_array($message) ? join("\n", $message) : $message),
            'stacktrace' => $this->get_backtrace_string(),
        );

        $ci =& get_instance();

        $subject = "Error";
        switch ($template) {
            case 'error_general':
                $subject = 'General Error';
                $response['error'] = 'general_error';

                if ((stristr($response['error_message'], 'disallowed characters') !== false)) {
                    $subject .= ': Disallowed URI Characters';
                    $response['error'] = 'invalid_uri';

                    $config = null;
                    $uri = null;
                    if (is_object($ci)) {
                        $config =& $ci->config;
                        $uri =& $ci->uri;
                    } else {
                        $config =& load_class('Config', 'core');
                        $uri =& load_class('URI', 'core');
                    }

                    if (is_object($config) && is_object($uri)) {
                        $uric = $config->item('permitted_uri_chars');
                        $qp = str_replace(array('\\-', '\-'), '-', preg_quote($uric, '-'));
                        $matches = array();
                        $str = $uri->uri_string();
                        $invertedRegex = "|([^" . $qp . "]+)|i";
                        preg_match_all($invertedRegex, $str, $matches);
                        $response['uri_string'] = $str;
                        $response['disallowed_character_matches'] = $matches;
                        $response['regex'] = $invertedRegex;
                    }
                }

                break;

            case 'error_db':
                $response['error'] = 'db_error';
                $subject = 'DB Error';
                break;
        }

        $output = '';
        $first = true;
        foreach ($response as $key => $value) {
            if (! $first) {
                $output .= "\n";
            }
            $first = false;

            $output .= $key . ': ';

            if (is_array($value) || is_object($value)) {
                $output .= "\n" . print_r($value) . "\n";
            } else if (is_string($value) && (strlen($value) > 80)) {
                $output .= "\nt$value\n";
            } else {
                $output .= $value;
            }
        }

        $email = $output
            . "\n\n_SERVER:"
            . "\n" . print_r($_SERVER, true)
            . "\n\n_SESSION:"
            . "\n" . (isset($_SESSION) ? print_r($_SESSION, true) : 'UNSET');

        $requestDump = print_r($_REQUEST, true);
        if (strlen($requestDump) < 512) {
            $email .= "\n\n_REQUEST:\n" . $requestDump;
        } else {
            $email .= "\n\n_REQUEST: (excluded due to size)";
        }

        $email .= ""
            . "\n\n---EOM---\n";

        $subject = $this->projectName . ' ' . $subject;
        if (ENVIRONMENT !== 'production') {
            $subject .= ' - ' . ENVIRONMENT;
        }

        @mail(DEVELOPER_EMAILS, $subject, $email);

        if (defined('ERROR_HANDLER_LOG')) {
            file_put_contents(ERROR_HANDLER_LOG, $email, FILE_APPEND);
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        if ($this->outputFormat == 'text') {
            print "\n$output\n";
            exit(1);
        }

        if ($this->outputFormat == 'json') {
            // If we're in the API, don't output anything, as it may mess up the other output
            if (ENVIRONMENT == 'production') {
                $response = array(
                    'status' => 'error',
                    'error' => 'internal_server_error',
                    'error_message' => "A technical fault has occurred. The developers have been notified. Please contact support if the issue persists.",
                );
            }
            print json_encode($response, true);
            exit();
        }

        include(APPPATH . 'errors/error_general.php');
        exit(1);
    }


    /**
     * Native PHP error handler
     *
     * @param    string $severity the error severity
     * @param    string $message the error string
     * @param    string $filepath the error filepath
     * @param    string $line the error line number
     * @return    string Output to diaplay
     */
    public function show_php_error($severity, $message, $filepath, $line)
    {
        $severity = (! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

        $filepath = str_replace("\\", "/", $filepath);

        // For safety reasons we do not show the full file path
        if (false !== strpos($filepath, '/')) {
            $x = explode('/', $filepath);
            $filepath = $x[count($x) - 2] . '/' . end($x);
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        // Hide some errors from the visitor
        $hideMessageLevels = array('runtime notice', 'notice', 'warning', 'user notice', 'user warning');
        $hideErrors = (in_array(strtolower($severity), $hideMessageLevels));

        $stacktrace = $this->get_backtrace_string();

        if ((ENVIRONMENT == 'development') && $hideErrors && ($this->outputFormat == 'html')) {
            include(APPPATH . 'errors/error_php_inline.php');
        }
        if ($this->outputFormat == 'text') {
            $msg = "\n\n{$severity}: {$message}"
                . "\nLocation: {$filepath} @ line {$line}"
                . "\n\nSTACK TRACE:\n" . $stacktrace
                . "\n";
            print $msg;
        }

        $email = "Severity: {$severity}"
            . "\nMessage: {$message}"
            . "\nFilename: {$filepath}"
            . "\nLine: {$line}"
            . "\n\nSTACK TRACE:\n" . $stacktrace
            . "\n\n_SERVER:\n" . print_r($_SERVER, true)
            . "\n\n_SESSION:\n" . (isset($_SESSION) ? print_r($_SESSION, true) : 'UNSET');

        $requestDump = print_r($_REQUEST, true);
        if (strlen($requestDump) < 512) {
            $email .= "\n\n_REQUEST:\n" . $requestDump;
        } else {
            $email .= "\n\n_REQUEST: (excluded due to size)";
        }

        $email .= ""
            . "\n\n--- EOM ---\n";

        $subject = $this->projectName . ' PHP Error';
        if (ENVIRONMENT != 'production') {
            $subject .= ' - ' . ENVIRONMENT;
        }
        $emails = DEVELOPER_EMAILS;
        @mail($emails, $subject, $email);

        if (defined('ERROR_HANDER_LOG')) {
            file_put_contents(ERROR_HANDLER_LOG, $email, FILE_APPEND);
        }

        // Error has already been displayed inline
        if ($hideErrors) {
            return false;
        }

        if ($this->outputFormat == 'html') {
            include(APPPATH . 'errors/error_php.php');
        }

        if ($this->outputFormat == 'json') {
            if (ENVIRONMENT == 'production') {
                $response = array(
                    'status' => 'error',
                    'error' => 'internal_server_error',
                    'error_message' => "A technical fault has occurred. The developers have been notified. Please contact support if the issue persists.",
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'error' => $severity,
                    'error_message' => $message,
                    'filepath' => $filepath,
                    'line' => $line,
                    '_server' => $_SERVER,
                    'backtrace' => debug_backtrace(0),
                );
            }
            print json_encode($response);
        }

        exit(1);
    }


    /**
     * @param Exception $e
     */
    public function show_exception($e)
    {
        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        // Set variables used in php_error template
        $severity = 'Uncaught Exception';
        $stacktrace = $e->getTraceAsString();
        $line = $e->getLine();
        $message = $e->getMessage();
        $filepath = $e->getFile();

        $ci = null;
        if (class_exists('CI_Controller')) {
            $ci =& get_instance();
        }

        $email = $this->get_exception_stack_string($e);

        if (is_object($ci) && isset($ci->session) && is_object($ci->session)) {
            $email .= "\n\nSession:\n" . print_r($ci->session->all_userdata(), true);
        } else {
            $email .= "\n\nSession:\n" . (isset($_SESSION) ? print_r($_SESSION, true) : 'UNSET');
        }

        $requestDump = print_r($_REQUEST, true);
        if (strlen($requestDump) < 512) {
            $email .= "\n\n_REQUEST:\n" . $requestDump;
        } else {
            $email .= "\n\n_REQUEST: (excluded due to size)";
        }

        $email .= ""
            . "\n\n_SERVER:\n" . print_r($_SERVER, true)
            . "\n\n--- EOM ---\n";

        $subject = PROJECT_NAME . ' Uncaught Exception';
        if (ENVIRONMENT !== 'production') {
            $subject .= ' - ' . ENVIRONMENT;
        }
        mail(DEVELOPER_EMAILS, $subject, $email);

        if (defined('ERROR_HANDLER_LOG')) {
            file_put_contents(ERROR_HANDLER_LOG, $email, FILE_APPEND);
        }

        if ($this->outputFormat == 'html') {
            ob_start();
            include(APPPATH . 'errors/error_php.php');
            $buffer = ob_get_contents();
            ob_end_clean();
            echo $buffer;
        } else if ($this->outputFormat == 'text') {
            print "\nUncaught Exception: {$message}"
                . "\nLocation: {$filepath} @ line {$line}"
                . "\n\n{$stacktrace}\n";
        } else if ($this->outputFormat == 'json') {
            if (ENVIRONMENT == 'production') {
                $response = array(
                    'status' => 'error',
                    'error' => 'internal_server_error',
                    'error_message' => "A technical fault has occurred. The developers have been notified. Please contact support if the issue persists.",
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'error' => 'uncaught exception',
                    'error_message' => $e->getMessage(),
                    'exception' => $this->get_exception_as_array($e),
                );
            }
            print json_encode($response);
        }

        exit(1);
    }


    protected function get_exception_as_array(Exception $e)
    {
        $retval = array(
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'previous' => (is_object($e->getPrevious()) ? $this->get_exception_as_array($e->getPrevious()) : null),
            'trace' => $e->getTrace(),
        );
        return $retval;
    }


    //generate stack trace ignoreing most of CI's crap
    protected function get_backtrace_string()
    {
        $stacktrace = "";
        $backtrace = debug_backtrace();
        $skipClassList = array('CI_Exceptions', 'MY_Exceptions');
        foreach ($backtrace as $index => $call) {
            $index++;

            if (array_key_exists('class', $call) && in_array($call['class'], $skipClassList)) {
                $stacktrace = $index . ": {$call['class']} - Skipped\n";
                continue;
            }

            if (array_key_exists('file', $call) && (stripos($call['file'], FCPATH) === 0)) {
                $call['file'] = str_replace(FCPATH, '', $call['file']);
            }

            $args = "";
            if (substr(@$call['class'], 0, 2) != 'CI' && is_array(@$call['args'])) {
                foreach (@$call['args'] as $arg) {
                    if ($args != "") {
                        $args .= ", ";
                    }

                    if (is_object($arg)) {
                        $args .= get_class($arg);
                    } else if (is_array($arg)) {
                        $args .= 'Array[]';
                    } else if (is_string($arg)) {
                        $args .= '"' . $arg . '"';
                    } else {
                        $args .= $arg;
                    }
                }
            }
            $stacktrace .= $index . ': ' . @$call['class'] . '::' . @$call['function'] . "($args)"
                . "\n\t" . @$call['file'] . '(' . @$call['line'] . ')'
                . "\n";
        }

        return $stacktrace;
    }


    /**
     * @param Exception $e
     * @return string
     */
    protected function get_exception_stack_string($e)
    {
        $email = "Message: {$e->getMessage()}"
            . "\nCode: " . $e->getCode()
            . "\nLine: " . $e->getLine()
            . "\nFile: " . $e->getFile()
            . "\nStack Trace:\n" . $e->getTraceAsString()
//            ."\n\nRaw trace:\n". print_r($e->getTrace(), TRUE)
            . "\n\n";;

        if (is_object($e->getPrevious())) {
            $email .= "--- Previous Exception ---\n"
                . $this->get_exception_stack_string($e->getPrevious());
        }

        return $email;
    }
}
