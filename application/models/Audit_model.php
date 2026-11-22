<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_model extends MY_Model
{
    protected $table = 'audit_logs';
    protected $audit_columns = FALSE;

    /**
     * Write an audit entry.
     */
    public function log($action, $module = NULL, $table = NULL, $record_id = NULL, $description = NULL, $old = NULL, $new = NULL)
    {
        $user = $this->access_control->user();

        $data = array(
            'user_id'     => $user ? $user['id'] : NULL,
            'username'    => $user ? $user['username'] : $this->session->userdata('uie_attempt_user'),
            'action'      => $action,
            'module'      => $module,
            'table_name'  => $table,
            'record_id'   => $record_id !== NULL ? (string) $record_id : NULL,
            'description' => $description,
            'old_values'  => $old !== NULL ? json_encode($old) : NULL,
            'new_values'  => $new !== NULL ? json_encode($new) : NULL,
            'ip_address'  => $this->input->ip_address(),
            'user_agent'  => substr((string) $this->input->user_agent(), 0, 255),
        );

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Paginated audit trail with filters.
     */
    public function paginate_logs($limit, $offset, array $f = array())
    {
        $this->_log_filters($f);
        $this->db->order_by('id', 'DESC');
        return $this->db->limit($limit, $offset)->get($this->table)->result();
    }

    public function count_logs(array $f = array())
    {
        $this->_log_filters($f);
        return (int) $this->db->count_all_results($this->table);
    }

    protected function _log_filters(array $f)
    {
        if ( ! empty($f['user_id']))   $this->db->where('user_id', $f['user_id']);
        if ( ! empty($f['module']))   $this->db->where('module', $f['module']);
        if ( ! empty($f['action']))   $this->db->like('action', $f['action']);
        if ( ! empty($f['date_from']))$this->db->where('created_at >=', $f['date_from'].' 00:00:00');
        if ( ! empty($f['date_to']))  $this->db->where('created_at <=', $f['date_to'].' 23:59:59');
        if ( ! empty($f['q']))
        {
            $this->db->group_start()
                ->like('username', $f['q'])
                ->or_like('description', $f['q'])
                ->or_like('action', $f['q'])
                ->group_end();
        }
    }
}
