<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UIEIMS base model - shared CRUD helpers using Query Builder.
 */
class MY_Model extends CI_Model
{
    /** @var string Table name */
    protected $table = '';

    /** @var string Primary key column */
    protected $primary_key = 'id';

    /** @var bool Table has created_by/updated_by audit columns */
    protected $audit_columns = TRUE;

    /** @var string|array Default order */
    protected $default_order = NULL;

    public function get($id)
    {
        return $this->db->where($this->primary_key, $id)
            ->limit(1)->get($this->table)->row();
    }

    public function get_where(array $where, $order = NULL)
    {
        $order = $order ?: $this->default_order;
        if ($order) $this->db->order_by($order);
        return $this->db->where($where)->get($this->table)->result();
    }

    public function get_row_where(array $where)
    {
        return $this->db->where($where)->limit(1)->get($this->table)->row();
    }

    public function insert(array $data)
    {
        if ($this->audit_columns)
        {
            $uid = $this->_uid();
            if ( ! isset($data['created_by'])) $data['created_by'] = $uid;
            $data['updated_by'] = $uid;
        }
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        if ($this->audit_columns)
        {
            $data['updated_by'] = $this->_uid();
        }
        return $this->db->where($this->primary_key, $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where($this->primary_key, $id)->delete($this->table);
    }

    public function count(array $where = array())
    {
        return (int) $this->db->where($where)->count_all_results($this->table);
    }

    public function exists(array $where, $exclude_id = NULL)
    {
        if ($exclude_id !== NULL)
        {
            $this->db->where($this->primary_key.' !=', $exclude_id);
        }
        return (bool) $this->db->where($where)->limit(1)->count_all_results($this->table);
    }

    /**
     * Server-side paginated listing.
     * $filters['where'] - exact match pairs
     * $filters['like']  - array of array(field => term) OR'd together
     */
    public function paginate($limit, $offset, array $filters = array(), $order = NULL)
    {
        $this->_apply_filters($filters);
        $order = $order ?: $this->default_order;
        if ($order) $this->db->order_by($order);
        return $this->db->limit($limit, $offset)->get($this->table)->result();
    }

    public function count_filtered(array $filters = array())
    {
        $this->_apply_filters($filters);
        return (int) $this->db->count_all_results($this->table);
    }

    protected function _apply_filters(array $filters)
    {
        if ( ! empty($filters['where']))
        {
            $this->db->where($filters['where']);
        }
        if ( ! empty($filters['like']))
        {
            $this->db->group_start();
            $first = TRUE;
            foreach ($filters['like'] as $field => $term)
            {
                $first ? $this->db->like($field, $term) : $this->db->or_like($field, $term);
                $first = FALSE;
            }
            $this->db->group_end();
        }
        if ( ! empty($filters['custom']))
        {
            foreach ($filters['custom'] as $clause)
            {
                $this->db->where($clause, NULL, FALSE);
            }
        }
    }

    protected function _uid()
    {
        $uid = $this->session->userdata('uie_uid');
        return $uid ? (int) $uid : NULL;
    }
}
