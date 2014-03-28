<?php

/**
 * Extended simple implementation model that provides a lot of common functionality with little effort.
 *
 * If you don't want any of this additional functionality, I recommend you extend MY_BasicModel instead.
 */
class MY_Model extends MY_BasicModel {

    /**
     * @var string What is the table name for this table?
     */
    protected $table = NULL;

    /**
     * @var string What is the primary key for this table?
     */
    protected $keyField = NULL;

    /**
     * @var bool Does this table use soft deletes? (Have a 'deleted' boolean column)
     */
    protected $softDelete = TRUE;

    /**
     * @var bool Does this table have dt_created and dt_deleted fields?
     */
    protected $hasDtFields = TRUE;

    /**
     * @var bool Does this table has a dt_modified field?
     */
    protected $hasDtModified = FALSE;


    public function __construct() {
        parent::__construct();

        // Due to a quirk in the way CI loads models, we have to check what class we're an instance of here
        if ((get_class($this) != 'MY_Model') && (($this->table === NULL) || ($this->keyField === NULL))) {
            trigger_error("Table or KeyField properties not set. Did you want to use MY_BasicModel instead?", E_USER_NOTICE);
        }
    }


    /**
     * @param int $id
     * @param bool $includeDeleted If soft deletion is enabled, allow deleted items to be returned.
     * @return null|object
     */
    public function get($id, $includeDeleted = FALSE) {
        $where = array($this->keyField => $id);
        if ((!$includeDeleted) && $this->softDelete) {
            $where['deleted'] = FALSE;
        }
        $resultSet = $this->db->get_where($this->table, $where, 1);
        return (is_object($resultSet) && ($resultSet->num_rows() > 0)) ? $resultSet->row() : NULL;
    }


    /**
     * @param array $record
     * @return int New record ID
     */
    public function add(array $record) {
        if ($this->hasDtFields) {
            $this->db->set('dt_created', 'NOW()', FALSE);
        }
        $this->db->insert($this->table, $record);
        return $this->db->insert_id();
    }


    /**
     * @param int|array $id
     * @param array $record
     * @return int Affected rows
     */
    public function update($id, array $record) {
        $limit = NULL;
        $where = $id;
        if (!is_array($id)) {
            $where = array($this->keyField => $id);
            $limit = 1;
        }

        if ($this->hasDtModified) {
            $this->db->set('dt_modified', 'NOW()', FALSE);
        }
        $this->db->update($this->table, $record, $where, $limit);
        return $this->db->affected_rows();
    }


    public function delete($id) {
        if ($this->softDelete) {
            if ($this->hasDtFields) {
                $this->db->set('dt_deleted', 'NOW()', FALSE);
            }
            $this->db->update($this->table, array('deleted' => TRUE), array($this->keyField => $id), 1);
        } else {
            $this->db->delete($this->table, array($this->keyField => $id), 1);
        }
        return $this->db->affected_rows();
    }


    /**
     * @param array $searchParams Search Parameters
     * @return CI_DB_Result|null|bool
     */
    public function fetchBySearch(array $searchParams) {
        $table = $this->table;
        $this->db->from($table);
        $limit = NULL;
        $offset = '';
        $orderBy = NULL;
        $deleted = FALSE;
        $fields = array("`{$table}`.*");
        $action = 'select';

        foreach ($searchParams as $key => $value) {
            switch ($key) {
                case $this->keyField:
                    $this->db->where_in($key, $value);
                    break;

                case 'created_after':
                    if (is_object($value) && ($value instanceof DateTime)) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $this->db->where('dt_created >=', $value);
                    break;

                case 'created_before':
                    if (is_object($value) && ($value instanceof DateTime)) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $this->db->where('dt_created <=', $value);
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

                case 'fields':
                    if (!is_array($value)) {
                        return $this->returnError("Value for parameter 'fields' must be an array");
                    }
                    $fields = $value;
                    break;

                case 'additional_fields':
                    if (is_array($value)) {
                        foreach ($value as $entry) {
                            $fields[] = $entry;
                        }
                    } else {
                        $fields[] = $value;
                    }
                    break;

                case '_action':
                    $action = $value;
                    break;

                default:
                    return $this->returnError("Invalid search parameter specified: {$key}");
            }
        }

        if (($this->softDelete) && ($deleted !== NULL)) {
            $this->db->where('deleted', ($deleted ? 1 : 0));
        }

        $this->db->select(array_reverse($fields));
        if ($limit !== NULL) {
            $this->db->limit($limit, $offset);
        }
        if ($orderBy !== NULL) {
            $this->db->order_by($orderBy);
        }

        switch ($action) {
            case 'count':
                return $this->db->count_all_results();

            case 'delete':
                $this->db->delete();
                $retval = $this->db->affected_rows();
                $this->db->reset();
                return $retval;

            case 'select':
                $resultSet = $this->db->get();
                return (is_object($resultSet) && ($resultSet->num_rows() > 0)) ? $resultSet : NULL;

            default:
                return $this->returnError("Invalid action specified: {$action}");
        }
    }


    public function countAll() {
        if ($this->softDelete) {
            $this->db->where('deleted', FALSE);
        }
        return $this->db->count_all_results($this->table);
    }

}
