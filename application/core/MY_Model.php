<?php

/**
 * Extended simple implementation model that provides a lot of common functionality with little effort.
 *
 * If you don't want any of this additional functionality, I recommend you extend MY_BasicModel instead.
 */
class MY_Model extends MY_BasicModel
{

    /**
     * @var string What is the table name for this table?
     */
    protected $table = null;

    /**
     * @var string What is the primary key for this table?
     */
    protected $keyField = null;

    /**
     * @var bool Does this table use soft deletes? (Have a 'deleted' boolean column)
     */
    protected $softDelete = true;

    /**
     * @var bool Does this table have dt_created and dt_deleted fields?
     */
    protected $hasDtFields = true;

    /**
     * @var bool Does this table has a dt_modified field?
     */
    protected $hasDtModified = false;

    /**
     * @var null Default order for results from fetchBySearch. This should be an SQL string.
     */
    protected $defaultSearchResultsOrder = null;

    /**
     * @var null|array Fields to select for fetchBySearch method
     */
    protected $fetchBySearchFields = null;


    public function __construct()
    {
        parent::__construct();

        // Due to a quirk in the way CI loads models, we have to check what class we're an instance of here
        if ((get_class($this) != 'MY_Model') && (($this->table === null) || ($this->keyField === null))) {
            trigger_error(
                "Table or KeyField properties not set. Did you want to use MY_BasicModel instead?",
                E_USER_NOTICE
            );
        }
    }


    /**
     * @param int $id
     * @param bool $includeDeleted If soft deletion is enabled, allow deleted items to be returned.
     * @return null|object
     */
    public function get($id, $includeDeleted = false)
    {
        $where = array($this->keyField => $id);
        if ((! $includeDeleted) && $this->softDelete) {
            $where['deleted'] = false;
        }
        $resultSet = $this->db->get_where($this->table, $where, 1);
        return (is_object($resultSet) && ($resultSet->num_rows() > 0)) ? $resultSet->row() : null;
    }


    /**
     * @param array $record
     * @return int New record ID
     */
    public function add(array $record)
    {
        if ($this->hasDtFields) {
            $this->db->set('dt_created', 'NOW()', false);
        }
        $this->db->insert($this->table, $record);
        return $this->db->insert_id();
    }


    /**
     * @param int|array $id
     * @param array $record
     * @return int Affected rows
     */
    public function update($id, array $record)
    {
        $limit = null;
        $where = $id;
        if (! is_array($id)) {
            $where = array($this->keyField => $id);
            $limit = 1;
        }

        if ($this->hasDtModified) {
            $this->db->set('dt_modified', 'NOW()', false);
        }
        $this->db->update($this->table, $record, $where, $limit);
        return $this->db->affected_rows();
    }


    public function delete($id)
    {
        if ($this->softDelete) {
            if ($this->hasDtFields) {
                $this->db->set('dt_deleted', 'NOW()', false);
            }
            $this->db->update($this->table, array('deleted' => true), array($this->keyField => $id), 1);
        } else {
            $this->db->delete($this->table, array($this->keyField => $id), 1);
        }
        return $this->db->affected_rows();
    }


    /**
     * @param array $searchParams Search Parameters
     *
     * @return CI_DB_Result|null|bool
     */
    public function fetchBySearch(array $searchParams)
    {
        $table = $this->table;
        $this->db->from($table);
        $limit = null;
        $offset = '';
        $orderBy = $this->defaultSearchResultsOrder;
        $deleted = false;
        $this->fetchBySearchFields = array("`{$table}`.*");
        $action = 'select';

        foreach ($searchParams as $key => $value) {
            if (is_array($value) && count($value) < 1) {
                return $this->returnError("Value for parameter '{$key}' cannot be an empty array");
            }

            switch ($key) {
                case 'deleted':
                    $deleted = $value;
                    break;

                case '_limit':
                    $limit = $value;
                    break;

                case '_offset':
                    $offset = $value;
                    break;

                case '_order_by':
                    $orderBy = $value;
                    break;

                case '_fields':
                    if (! is_array($value)) {
                        return $this->returnError("Value for parameter 'fields' must be an array");
                    }
                    $this->fetchBySearchFields = $value;
                    break;

                case '_additional_fields':
                    if (is_array($value)) {
                        foreach ($value as $entry) {
                            $this->fetchBySearchFields[] = $entry;
                        }
                    } else {
                        $this->fetchBySearchFields[] = $value;
                    }
                    break;

                case '_action':
                    $action = $value;
                    break;

                default:
                    $handled = $this->fetchBySearchCoreParam($key, $value);
                    if (! $handled) {
                        $handled = $this->fetchBySearchParam($key, $value);
                        if (! $handled) {
                            return $this->returnError("Invalid search parameter specified: {$key}");
                        }
                    }
            }
        }

        if (($this->softDelete) && ($deleted !== null)) {
            $this->db->where("{$table}.deleted", ($deleted ? 1 : 0));
        }

        $this->db->select(array_reverse($this->fetchBySearchFields));
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        if ($orderBy !== null) {
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
                return (is_object($resultSet) && ($resultSet->num_rows() > 0)) ? $resultSet : null;

            default:
                return $this->returnError("Invalid action specified: {$action}");
        }
    }


    protected function fetchBySearchCoreParam($key, $value)
    {

        switch ($key) {
            case $this->keyField:
                $this->db->where_in($this->table . '.' . $key, $value);
                return true;

            case 'created_after':
                if (is_object($value) && ($value instanceof DateTime)) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $this->db->where('dt_created >=', $value);
                return true;

            case 'created_before':
                if (is_object($value) && ($value instanceof DateTime)) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $this->db->where('dt_created <=', $value);
                return true;
        }

        return false;
    }


    /**
     * Hook to handle extra search parameters for fetchBySearch without redefining the entire method.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool Parameter was handled?
     */
    protected function fetchBySearchParam($key, $value)
    {
        return false;
    }


    public function countAll()
    {
        if ($this->softDelete) {
            $this->db->where('deleted', false);
        }
        return $this->db->count_all_results($this->table);
    }
}
