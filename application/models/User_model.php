<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends MY_Model
{
    protected $table = 'users';
    protected $default_order = 'last_name ASC, first_name ASC';

    // ---------------------------------------------------------------
    // Lookups
    // ---------------------------------------------------------------

    public function find_by_username($username)
    {
        return $this->db->where('username', $username)->limit(1)->get($this->table)->row_array();
    }

    public function find_for_login($username)
    {
        return $this->db->group_start()
            ->where('username', $username)
            ->or_where('email', $username)
            ->group_end()
            ->limit(1)->get($this->table)->row_array();
    }

    // ---------------------------------------------------------------
    // Roles / permissions / assignments
    // ---------------------------------------------------------------

    public function get_user_roles($user_id)
    {
        return $this->db->select('r.id, r.name, r.slug, r.level')
            ->from('user_roles ur')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $user_id)
            ->where('r.status', 'active')
            ->get()->result_array();
    }

    /** Flat array of permission slugs across all user roles. */
    public function get_user_permissions($user_id)
    {
        $rows = $this->db->select('p.slug')
            ->from('user_roles ur')
            ->join('role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $user_id)
            ->get()->result_array();
        return array_values(array_unique(array_column($rows, 'slug')));
    }

    public function get_primary_assignment($user_id)
    {
        return $this->db->where('user_id', $user_id)
            ->where('status', 'active')
            ->order_by('is_primary DESC, id ASC')
            ->limit(1)->get('user_assignments')->row_array();
    }

    public function set_roles($user_id, array $role_ids)
    {
        $this->db->where('user_id', $user_id)->delete('user_roles');
        if (empty($role_ids)) return;
        $batch = array();
        foreach ($role_ids as $rid)
        {
            $batch[] = array('user_id' => $user_id, 'role_id' => (int) $rid);
        }
        $this->db->insert_batch('user_roles', $batch);
    }

    public function get_role_ids($user_id)
    {
        return array_map('intval', array_column(
            $this->db->select('role_id')->where('user_id', $user_id)->get('user_roles')->result_array(),
            'role_id'
        ));
    }

    public function save_assignment($user_id, $region_id, $division_id, $school_id)
    {
        // Deactivate previous primary assignments, then insert new one (or update in place)
        $this->db->where('user_id', $user_id)->where('is_primary', 1)
            ->update('user_assignments', array('is_primary' => 0, 'status' => 'inactive'));

        if ($region_id === NULL && $division_id === NULL && $school_id === NULL)
        {
            return TRUE;
        }
        return $this->db->insert('user_assignments', array(
            'user_id'     => $user_id,
            'region_id'   => $region_id   ?: NULL,
            'division_id' => $division_id ?: NULL,
            'school_id'   => $school_id   ?: NULL,
            'is_primary'  => 1,
            'status'      => 'active',
            'created_by'  => $this->_uid(),
        ));
    }

    // ---------------------------------------------------------------
    // Listing / detail
    // ---------------------------------------------------------------

    public function paginate_with_roles($limit, $offset, array $f = array())
    {
        $this->_filters($f);
        $rows = $this->db->select('u.*,
                (SELECT GROUP_CONCAT(r.name SEPARATOR ", ") FROM uie_user_roles ur JOIN uie_roles r ON r.id = ur.role_id WHERE ur.user_id = u.id) AS role_names,
                (SELECT GROUP_CONCAT(r.slug SEPARATOR ",") FROM uie_user_roles ur JOIN uie_roles r ON r.id = ur.role_id WHERE ur.user_id = u.id) AS role_slugs')
            ->from($this->table.' u')
            ->order_by('u.last_name, u.first_name')
            ->limit($limit, $offset)
            ->get()->result();

        // Attach assignment labels
        if ($rows)
        {
            $ids = array_column($rows, 'id');
            $assigns = $this->db->select('ua.user_id, rgn.name AS region_name, d.name AS division_name, s.name AS school_name')
                ->from('user_assignments ua')
                ->join('regions rgn', 'rgn.id = ua.region_id', 'left')
                ->join('divisions d', 'd.id = ua.division_id', 'left')
                ->join('schools s', 's.id = ua.school_id', 'left')
                ->where_in('ua.user_id', $ids)
                ->where('ua.is_primary', 1)->where('ua.status', 'active')
                ->get()->result_array();
            $map = array();
            foreach ($assigns as $a) $map[$a['user_id']] = $a;
            foreach ($rows as $row)
            {
                $row->assignment = isset($map[$row->id]) ? $map[$row->id] : NULL;
            }
        }
        return $rows;
    }

    public function count_filtered(array $f = array())
    {
        $this->_filters($f);
        return (int) $this->db->count_all_results($this->table.' u');
    }

    protected function _filters(array $f)
    {
        if ( ! empty($f['status'])) $this->db->where('u.status', $f['status']);
        if ( ! empty($f['role_id']))
        {
            $this->db->where('u.id IN (SELECT user_id FROM uie_user_roles WHERE role_id = '.(int) $f['role_id'].')', NULL, FALSE);
        }
        if ( ! empty($f['q']))
        {
            $this->db->group_start()
                ->like('u.username', $f['q'])
                ->or_like('u.email', $f['q'])
                ->or_like('u.first_name', $f['q'])
                ->or_like('u.last_name', $f['q'])
                ->group_end();
        }
    }

    public function detail($id)
    {
        $user = $this->db->select('u.*')->from($this->table.' u')->where('u.id', $id)->limit(1)->get()->row();
        if ($user)
        {
            $user->roles      = $this->get_user_roles($id);
            $user->assignment = $this->db->select('ua.*, rgn.name AS region_name, d.name AS division_name, s.name AS school_name, s.school_id AS deped_school_id')
                ->from('user_assignments ua')
                ->join('regions rgn', 'rgn.id = ua.region_id', 'left')
                ->join('divisions d', 'd.id = ua.division_id', 'left')
                ->join('schools s', 's.id = ua.school_id', 'left')
                ->where('ua.user_id', $id)->where('ua.is_primary', 1)
                ->limit(1)->get()->row();
        }
        return $user;
    }

    // ---------------------------------------------------------------
    // Auth helpers
    // ---------------------------------------------------------------

    public function record_failed_login($user_id, $max_attempts, $lock_minutes)
    {
        $this->db->set('failed_attempts', 'failed_attempts + 1', FALSE)
            ->where('id', $user_id)->update($this->table);

        $row = $this->db->select('failed_attempts')->where('id', $user_id)->get($this->table)->row();
        if ($row && (int) $row->failed_attempts >= (int) $max_attempts)
        {
            $this->db->where('id', $user_id)->update($this->table, array(
                'failed_attempts' => 0,
                'locked_until'    => date('Y-m-d H:i:s', time() + ((int) $lock_minutes * 60)),
                'status'          => 'locked',
            ));
            return 'locked';
        }
        return 'failed';
    }

    public function record_successful_login($user_id)
    {
        $this->db->where('id', $user_id)->update($this->table, array(
            'failed_attempts' => 0,
            'locked_until'    => NULL,
            'last_login_at'   => date('Y-m-d H:i:s'),
            'last_login_ip'   => $this->input->ip_address(),
        ));
    }

    public function unlock($user_id)
    {
        return $this->db->where('id', $user_id)->update($this->table, array(
            'status'          => 'active',
            'failed_attempts' => 0,
            'locked_until'    => NULL,
            'updated_by'      => $this->_uid(),
        ));
    }

    public function username_exists($username, $exclude_id = NULL)
    {
        return $this->exists(array('username' => $username), $exclude_id);
    }

    public function email_exists($email, $exclude_id = NULL)
    {
        return $this->exists(array('email' => $email), $exclude_id);
    }
}
