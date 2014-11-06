<?php

class Notifications
{

    public function email($msg, $subject)
    {
        $msg = "Automated Notification:\n" . $msg;
        $msg .= "\n\n_SERVER: " . print_r($_SERVER, true);
        $msg .= "\n\nBacktrace: " . $this->get_backtrace_string();

        $requestDump = print_r($_REQUEST, true);
        if (strlen($requestDump) < 512) {
            $msg .= "\n\n_REQUEST:\n" . $requestDump;
        } else {
            $msg .= "\n\n_REQUEST: (excluded due to size)";
        }

        $msg .= "\n\n--- EOM ---\n";
        $subject = PROJECT_NAME . " Notification: " . $subject;

        mail(DEVELOPER_EMAILS, $subject, $msg);
    }


    /**
     * @param Exception $e
     * @return string
     */
    public function get_exception_stack_string($e)
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


    protected function get_backtrace_string()
    {
        $stacktrace = "";
        $backtrace = debug_backtrace();
        $skipClassList = array('Notifications');
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

            // Silence logged errors
            $keys = array('class', 'function', 'file', 'line');
            foreach ($keys as $key) {
                if (! array_key_exists($key, $call)) {
                    $call[$key] = '';
                }
            }

            $stacktrace .= $index . ': ' . @$call['class'] . '::' . @$call['function'] . "($args)"
                . "\n\t" . @$call['file'] . '(' . @$call['line'] . ')'
                . "\n";
        }

        return $stacktrace;
    }
}
