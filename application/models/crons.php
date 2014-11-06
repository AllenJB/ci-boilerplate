<?php

/**
 * Cron handling model
 *
 * Logs cron runs to cron_logs
 * Handles cron locks with cron_locks
 */
class Crons extends MY_BasicModel
{

    protected $table = 'cron_locks';

    protected $keyField = 'lockid';

    protected $softDelete = false;


    public function __construct()
    {
        parent::__construct();
    }


    public function addLog($record)
    {
        $this->db->insert('cron_logs', $record);
        return $this->db->insert_id();
    }


    public function gcLogs()
    {
        $sql = "DELETE FROM cron_logs WHERE dt_started < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $this->db->query($sql);
    }


    public function isLocked($cronName)
    {
        $resultSet = $this->db->get_where($this->table, array('cron' => $cronName, 'locked' => 1), 1);
        $dbLocked = (is_object($resultSet) && ($resultSet->num_rows() > 0));

        if (! $dbLocked) {
            return false;
        }

        $pid = $resultSet->row()->pid;
        if ($pid === null) {
            throw new Exception("NULL PID");
        }

        return file_exists('/proc/' . $pid);
    }


    public function lock($cronName, $rotation = 'process')
    {
        $table = $this->table;
        $where = array('cron' => $cronName);
        $resultSet = $this->db->get_where($table, $where, 1);
        if (is_object($resultSet) && ($resultSet->num_rows() > 0)) {
            $lockId = $resultSet->row()->lockid;

            $set = array(
                'dt_ended' => null,
                'locked' => true,
                'file_rotation' => $rotation,
                'pid' => getmypid(),
            );

            $this->db->set('dt_started', 'NOW()', false);
            $this->db->update($table, $set, $where, 1);
            return $lockId;
        }

        $record = array(
            'cron' => $cronName,
            'locked' => 1,
            'dt_ended' => null,
            'file_rotation' => $rotation,
            'pid' => getmypid(),
        );
        $this->db->set('dt_started', 'NOW()', false);
        $this->db->insert($table, $record);
        return $this->db->insert_id();
    }


    public function unlock($lockId)
    {
        $where = array('lockid' => $lockId);
        $this->db->set('dt_ended', 'NOW()', false);
        $this->db->update($this->table, array('locked' => 0), $where, 1);
        return $this->db->affected_rows();
    }


    public function getByCron($cronName)
    {
        $resultSet = $this->db->get_where($this->table, array('cron' => $cronName), 1);
        return (is_object($resultSet) && ($resultSet->num_rows() > 0)) ? $resultSet->row() : null;
    }


    /**
     * @param array $searchParams Search Parameters
     * @return CI_DB_Result|null|bool
     */
    public function fetchBySearch(array $searchParams)
    {
        $table = $this->table;
        $this->db->from($table);
        $limit = null;
        $offset = '';
        $orderBy = null;
        $deleted = false;

        foreach ($searchParams as $key => $value) {
            switch ($key) {
                case $this->keyField:
                    $this->db->where_in($key, $value);
                    break;

                case 'file_rotation':
                    $this->db->where_in($key, $value);
                    break;

                case 'deleted':
                    $deleted = $value;
                    break;

                case 'limit':
                    $limit = $value;
                    break;

                case 'offset':
                    $offset = $value;
                    break;

                case 'order_by':
                    $orderBy = $value;
                    break;

                default:
                    trigger_error("Invalid search parameter specified: {$key}", E_USER_ERROR);
                    $this->db->reset();
                    return false;
            }
        }

        if (($this->softDelete) && ($deleted !== null)) {
            $this->db->where('deleted', ($deleted ? 1 : 0));
        }

        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        if ($orderBy !== null) {
            $this->db->order_by($orderBy);
        }

        $resultSet = $this->db->get();
        return (is_object($resultSet) && ($resultSet->num_rows() > 0)) ? $resultSet : null;
    }
}
