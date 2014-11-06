<?php

class MY_BasicModel extends CI_Model
{


    public function __construct()
    {
        parent::__construct();
        $this->setSqlMode();
    }


    protected function setSqlMode()
    {
        $modes = array(
            'ERROR_FOR_DIVISION_BY_ZERO',
            'NO_ZERO_DATE',
            'NO_ZERO_IN_DATE',
            'STRICT_ALL_TABLES',
        );
        $this->db->query("SET sql_mode = '" . join(',', $modes) . "'");
    }


    public function getSqlMode()
    {
        $resultSet = $this->db->query('SELECT @@SESSION.sql_mode AS `sqlmode`;');
        return $resultSet->row()->sqlmode;
    }


    /**
     * Return the last DB error
     *
     * @return string|null Last DB error
     */
    public function lastError()
    {
        $errNo = $this->db->_error_number();
        return ($errNo > 0) ? $errNo . ' :: ' . $this->db->_error_message() : null;
    }


    /**
     * Return the last SQL query that was executed
     *
     * @return string Last query SQL
     */
    public function lastQuery()
    {
        return $this->db->last_query();
    }


    /**
     * Reconnect the database
     */
    public function reconnect()
    {
        $this->db->reconnect();
        if ($this->db->conn_id === false) {
            $this->db->db_connect();
            $this->setSqlMode();
        }
    }


    /**
     * Trigger a database error.
     * This method additionally attempts to reset the active query state.
     *
     * @param string $msg Error message
     * @return bool FALSE
     */
    public function returnError($msg)
    {
        trigger_error($msg, E_USER_ERROR);
        $this->db->reset();
        return false;
    }


    /**
     * Escape the values in an array.
     * This method works with both an array containing only values, and an associative array.
     * If a value is an object or array, it is left untouched.
     *
     * @param array $array Input
     * @return array Escaped array
     */
    protected function escapeArray(array $array)
    {
        if (! is_array($array)) {
            trigger_error('Parameter 0 ($array) is not an array');
            return $array;
        }

        foreach ($array as $key => $value) {
            if (! (is_array($value) || is_object($value))) {
                $array[$key] = $this->db->escape($value);
            }
        }
        return $array;
    }
}
