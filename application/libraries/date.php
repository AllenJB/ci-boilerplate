<?php

class Date {

    /**
     * @var string Short date format - used for both input from forms and output
     */
    protected $formatShort = 'Y-m-d';
    /**
     * @var string Short date format for JS - used for datepickers / JS code
     * Short JS MUST produce the same format as Short above or datepickers will break everything!
     */
    protected $formatShortJs = 'yy-mm-dd';

    /**
     * @var string Long date format - used for output
     */
    protected $formatLong = 'D jS M Y';

    /**
     * @var string "Int" date format - used for sorting
     */
    protected $formatInt = 'Ymd';

    /**
     * @var string Time format (for both short and long) - used for both input and output
     */
    protected $formatTime = 'H:i:s';

    protected $defaultFormat = 'short';


    protected $iso8601_dayOfWeek = array (
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday'
    );

    public function __construct() {
    }


    public function setDefaultFormat($format) {
        $retval = $this->defaultFormat;
        $this->defaultFormat = $format;
        return $retval;
    }


    /**
     * Format a given date/time string (expected to be in format 'Y-m-d H:i:s' or a DateTime object) for display.
     *
     * If the given date/time is invalid, returns FALSE
     *
     * @param string|DateTime $datetime
     * @param object|bool|string $default Default return value is date fails to parse. If 'NOW', will use the current date/time
     * @param string $format
     * @param bool $time
     * @return string|bool Formatted datetime or FALSE on error
     */
    public function display($datetime, $default = FALSE, $format = NULL, $time = FALSE) {
        $retVal = $default;
        if ($default === 'NOW()' || $default === 'NOW') {
            $retVal = new DateTime();
        }
        if ($format === NULL) {
            $format = $this->defaultFormat;
        }

        if ((!is_object($datetime)) && strlen($datetime)) {
            if (($datetime == '0000-00-00') || ($datetime == '0000-00-00 00:00:00')) {
                return FALSE;
            }
            $instring = $datetime;
            $datetime = NULL;
            try {
                $datetime = new DateTime($instring);
            } catch (Exception $e) {
                // Do nothing
            }
        }

        $formatString = $this->getFormat($format, $time);

        if (! (is_object($datetime) && strlen($formatString)) ) {
            if (is_object($retVal)) {
                if (strlen($formatString)) {
                    $retVal = $retVal->format($formatString);
                } else {
                    $retVal = FALSE;
                }
            }
            return $retVal;
        }

        return $datetime->format($formatString);
    }


    public function displayDT($datetime, $default = FALSE, $format = NULL) {
        return $this->display($datetime, $default, $format, TRUE);
    }


    /**
     * Retrieve a specific format string
     * @param $format
     * @param bool $time
     * @return string
     */
    public function getFormat($format = 'short', $time = FALSE) {
        $formatString = NULL;
        $format = strtolower(trim($format));
        switch ($format) {
            case 'short':
                $formatString = $this->formatShort;
                break;

            case 'long':
                $formatString = $this->formatLong;
                break;

            case 'shortjs':
                $formatString = $this->formatShortJs;
                break;

            case 'int':
                $formatString = $this->formatInt;
                break;

            default:
                trigger_error("Unrecognized format specified: {$format}");
                return FALSE;
                break;
        }

        if ($time) {
            $formatString .= ' '. $this->formatTime;
        }
        return $formatString;
    }


    /**
     * Parse a given date/time string
     * In addition to converting it to a DateTime object, we perform additional validity checks
     *
     * For example, DateTime considers 99/99/9999 as valid, just carrying over the extra days/months to months/years.
     * We consider this invalid.
     *
     * @param string $datetime The date/time string to parse
     * @param string $format Input format (usually 'short')
     * @return DateTime Validated date time object
     */
    public function parse($datetime, $format = 'short') {
        $formatString = $this->getFormat($format);

        $dt = NULL;
        try {
            $dt = DateTime::createFromFormat($formatString, $datetime);
        } catch (Exception $e) {
            // Do nothing
        }
        if (!is_object($dt)) {
            return FALSE;
        }

        $compareString = $dt->format($formatString);
        if ($compareString != $datetime) {
            return FALSE;
        }

        return $dt;
    }


    /**
     * Return an array of key value pairs for ISO8601 days of the week against English day names
     * @return array
     */
    public function getDaysOfWeekForISO8601() {
        return $this->iso8601_dayOfWeek;
    }

}
