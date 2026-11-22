<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends MY_Model
{
    protected $table = 'roles';
    protected $audit_columns = FALSE;
    protected $default_order = 'id ASC';

    public function active_options()
    {
        return $this->db->where('status', 'active')->order_by('id')->get($this->table)->result();
    }

    public function permissions_grouped()
    {
        $rows = $this->db->order_by('module, id')->get('permissions')->result();
        $grouped = array();
        foreach ($rows as $row)
        {
            $grouped[$row->module][] = $row;
        }
        return $grouped;
    }

    public function get_role_permission_ids($role_id)
    {
        return array_map('intval', array_column(
            $this->db->select('permission_id')->where('role_id', $role_id)->get('role_permissions')->result_array(),
            'permission_id'
        ));
    }

    public function set_permissions($role_id, array $permission_ids)
    {
        $this->db->where('role_id', $role_id)->delete('role_permissions');
        if (empty($permission_ids)) return;
        $batch = array();
        foreach ($permission_ids as $pid)
        {
            $batch[] = array('role_id' => $role_id, 'permission_id' => (int) $pid);
        }
        $this->db->insert_batch('role_permissions', $batch);
    }
}
