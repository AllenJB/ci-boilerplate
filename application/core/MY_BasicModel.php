<?php

class MY_BasicModel extends CI_Model {


    public function __construct() {
        parent::__construct();
    }


    /**
     * Return the last DB error
     * @return string|null Last DB error
     */
    public function lastError() {
        $errNo = $this->db->_error_number();
        return  ($errNo > 0) ? $errNo .' :: '. $this->db->_error_message() : NULL;
    }


    /**
     * Return the last SQL query that was executed
     * @return string Last query SQL
     */
    public function lastQuery() {
        return $this->db->last_query();
    }


    /**
     * Reconnect the database
     */
    public function reconnect() {
        $this->db->reconnect();
        if ($this->db->conn_id === FALSE) {
            $this->db->db_connect();
        }
    }


    /**
     * Trigger a database error.
     * This method additionally attempts to reset the active query state.
     * @param string $msg Error message
     * @return bool FALSE
     */
    public function returnError($msg) {
        trigger_error($msg, E_USER_ERROR);
        $this->db->reset();
        return FALSE;
    }


    /**
     * Escape the values in an array.
     * This method works with both an array containing only values, and an associative array.
     * If a value is an object or array, it is left untouched.
     *
     * @param array $array Input
     * @return array Escaped array
     */
    protected function escapeArray(array $array) {
        foreach ($array as $key => $value) {
            if (! (is_array($value) || is_object($value)) ) {
                $array[$key] = $this->db->escape($value);
            }
        }
        return $array;
    }

}
