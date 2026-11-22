<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends Superadmin_Controller
{
    protected $per_page = 15;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('User_model', 'Role_model', 'Region_model', 'Division_model', 'School_model'));
    }

    public function index()
    {
        $page = max(0, (int) $this->input->get('page'));
        $filters = array(
            'q'       => trim((string) $this->input->get('q')),
            'role_id' => (int) $this->input->get('role_id') ?: NULL,
            'status'  => $this->input->get('status') ?: NULL,
        );

        $total = $this->User_model->count_filtered($filters);
        $rows  = $this->User_model->paginate_with_roles($this->per_page, $page, $filters);

        $this->data['breadcrumbs'] = array('Super Admin' => site_url('superadmin/dashboard'), 'Users' => NULL);
        $this->render('superadmin/users/index', array(
            'page_title' => 'User Management - UIEIMS',
            'rows'       => $rows,
            'filters'    => $filters,
            'roles'      => $this->Role_model->active_options(),
            'pagination' => ci_pagination(site_url('superadmin/users'), $total, $this->per_page, 0),
            'total'      => $total,
        ));
    }

    public function create()
    {
        $this->access_control->require_permission('users.create');
        $this->_form(NULL);
    }

    public function edit($id = 0)
    {
        $this->access_control->require_permission('users.edit');
        $u = $this->User_model->detail((int) $id);
        if ( ! $u) show_404();
        $this->_form($u);
    }

    public function view($id = 0)
    {
        $u = $this->User_model->detail((int) $id);
        if ( ! $u) show_404();

        $this->load->model('Audit_model');
        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Users'       => site_url('superadmin/users'),
            $u->username  => NULL,
        );
        $this->render('superadmin/users/view', array(
            'page_title' => $u->first_name.' '.$u->last_name.' - User',
            'u'          => $u,
            'activity'   => $this->Audit_model->paginate_logs(20, 0, array('user_id' => $u->id)),
        ));
    }

    public function toggle($id = 0)
    {
        $this->access_control->require_permission('users.deactivate');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $u = $this->User_model->get((int) $id);
        if ( ! $u) show_404();
        if ((int) $u->id === (int) $this->user['id'])
        {
            $this->session->set_flashdata('error', 'You cannot deactivate your own account.');
            redirect('superadmin/users');
        }

        $new = $u->status === 'active' ? 'inactive' : 'active';
        $this->User_model->update($u->id, array('status' => $new, 'locked_until' => NULL, 'failed_attempts' => 0));
        audit_log($new === 'active' ? 'activate' : 'deactivate', 'users', 'users', $u->id,
            'User '.$u->username.' set to '.$new, array('status' => $u->status), array('status' => $new));

        $this->session->set_flashdata('success', 'User <b>'.e($u->username).'</b> is now '.$new.'.');
        redirect('superadmin/users');
    }

    public function unlock($id = 0)
    {
        $this->access_control->require_permission('users.edit');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $u = $this->User_model->get((int) $id);
        if ( ! $u) show_404();
        $this->User_model->unlock($u->id);
        audit_log('unlock', 'users', 'users', $u->id, 'Unlocked account '.$u->username);
        $this->session->set_flashdata('success', 'Account <b>'.e($u->username).'</b> unlocked.');
        redirect('superadmin/users');
    }

    /**
     * Reset a user's password (superadmin). Generates a temporary password.
     */
    public function reset_password($id = 0)
    {
        $this->access_control->require_permission('users.reset_password');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $u = $this->User_model->get((int) $id);
        if ( ! $u) show_404();

        $temp = 'Uie'.substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6).'!';
        $this->User_model->update($u->id, array(
            'password_hash'        => password_hash($temp, PASSWORD_BCRYPT),
            'must_change_password' => 1,
            'failed_attempts'      => 0,
            'locked_until'         => NULL,
            'status'               => $u->status === 'locked' ? 'active' : $u->status,
        ));
        audit_log('password_reset', 'users', 'users', $u->id, 'Password reset by administrator');

        $this->session->set_flashdata('success',
            'Password for <b>'.e($u->username).'</b> reset. Temporary password: <code>'.e($temp).'</code> — user must change it on next login.');
        redirect('superadmin/users');
    }

    protected function _form($u)
    {
        if ($this->input->method(TRUE) === 'POST')
        {
            $id = $u ? $u->id : NULL;
            $this->form_validation->set_rules('username', 'Username', 'required|trim|min_length[4]|max_length[60]|callback__unique_username['.($id ?: '').']');
            $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[120]|callback__unique_email['.($id ?: '').']');
            $this->form_validation->set_rules('first_name', 'First Name', 'required|trim|max_length[80]');
            $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|max_length[80]');
            $this->form_validation->set_rules('middle_name', 'Middle Name', 'trim|max_length[80]');
            $this->form_validation->set_rules('position', 'Position', 'trim|max_length[100]');
            $this->form_validation->set_rules('contact_number', 'Contact Number', 'trim|max_length[30]');
            $this->form_validation->set_rules('role_ids[]', 'Roles', 'required');
            if ( ! $u)
            {
                $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[72]');
                $this->form_validation->set_rules('password_confirm', 'Confirm Password', 'required|matches[password]');
            }

            // Role-driven assignment validation
            $role_ids = (array) $this->input->post('role_ids');
            $levels   = $this->_levels_for($role_ids);
            if (in_array('region', $levels) || in_array('division', $levels) || in_array('school', $levels))
            {
                $this->form_validation->set_rules('region_id', 'Region', 'required|integer');
            }
            if (in_array('division', $levels) || in_array('school', $levels))
            {
                $this->form_validation->set_rules('division_id', 'Division', 'required|integer');
            }
            if (in_array('school', $levels))
            {
                $this->form_validation->set_rules('school_id', 'School', 'required|integer');
            }

            if ($this->form_validation->run())
            {
                // verify org chain consistency (division belongs to region, school to division)
                if ( ! $this->_org_chain_valid()) {
                    $this->session->set_flashdata('error', 'Invalid organizational assignment (division/school does not belong to the selected parent).');
                    redirect(current_url());
                }

                $data = array(
                    'username'       => trim($this->input->post('username', TRUE)),
                    'email'          => trim($this->input->post('email', TRUE)),
                    'first_name'     => trim($this->input->post('first_name', TRUE)),
                    'middle_name'    => trim((string) $this->input->post('middle_name', TRUE)) ?: NULL,
                    'last_name'      => trim($this->input->post('last_name', TRUE)),
                    'position'       => trim((string) $this->input->post('position', TRUE)) ?: NULL,
                    'contact_number' => trim((string) $this->input->post('contact_number', TRUE)) ?: NULL,
                );

                if ($u)
                {
                    $this->User_model->update($u->id, $data);
                    $uid = $u->id;
                    audit_log('update', 'users', 'users', $uid, 'Updated user '.$data['username'], NULL, $data);
                }
                else
                {
                    $data['password_hash'] = password_hash((string) $this->input->post('password'), PASSWORD_BCRYPT);
                    $data['status'] = 'active';
                    $data['must_change_password'] = 1;
                    $data['password_changed_at'] = date('Y-m-d H:i:s');
                    $uid = $this->User_model->insert($data);
                    audit_log('create', 'users', 'users', $uid, 'Created user '.$data['username'], NULL, array_diff_key($data, array('password_hash' => 1)));
                }

                if ($this->access_control->has_permission('users.assign_roles'))
                {
                    $this->User_model->set_roles($uid, $role_ids);
                }
                $this->User_model->save_assignment(
                    $uid,
                    (int) $this->input->post('region_id') ?: NULL,
                    (int) $this->input->post('division_id') ?: NULL,
                    (int) $this->input->post('school_id') ?: NULL
                );

                $this->session->set_flashdata('success', 'User '.($u ? 'updated' : 'created').' successfully.');
                redirect('superadmin/users');
            }
        }

        $assignment = $u && isset($u->assignment) ? $u->assignment : NULL;
        $sel_region   = $this->input->post('region_id')   ?: ($assignment ? $assignment->region_id   : NULL);
        $sel_division = $this->input->post('division_id') ?: ($assignment ? $assignment->division_id : NULL);

        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Users'       => site_url('superadmin/users'),
            $u ? 'Edit User' : 'Create User' => NULL,
        );
        $this->render('superadmin/users/form', array(
            'page_title'   => ($u ? 'Edit' : 'Create').' User - UIEIMS',
            'u'            => $u,
            'roles'        => $this->Role_model->active_options(),
            'user_role_ids'=> $u ? $this->User_model->get_role_ids($u->id) : (array) $this->input->post('role_ids'),
            'regions'      => $this->Region_model->active_options(),
            'divisions'    => $sel_region ? $this->Division_model->options_by_region($sel_region, FALSE) : array(),
            'schools'      => $sel_division ? $this->School_model->options_by_division($sel_division, FALSE) : array(),
            'sel_region'   => $sel_region,
            'sel_division' => $sel_division,
            'sel_school'   => $this->input->post('school_id') ?: ($assignment ? $assignment->school_id : NULL),
        ));
    }

    protected function _levels_for(array $role_ids)
    {
        if (empty($role_ids)) return array();
        return array_column(
            $this->db->select('level')->where_in('id', array_map('intval', $role_ids))->get('roles')->result_array(),
            'level'
        );
    }

    protected function _org_chain_valid()
    {
        $region_id   = (int) $this->input->post('region_id');
        $division_id = (int) $this->input->post('division_id');
        $school_id   = (int) $this->input->post('school_id');

        if ($division_id)
        {
            $d = $this->Division_model->get($division_id);
            if ( ! $d) return FALSE;
            if ($region_id && (int) $d->region_id !== $region_id) return FALSE;
        }
        if ($school_id)
        {
            $s = $this->School_model->get($school_id);
            if ( ! $s) return FALSE;
            if ($division_id && (int) $s->division_id !== $division_id) return FALSE;
        }
        return TRUE;
    }

    public function _unique_username($v, $id)
    {
        if ($this->User_model->username_exists(trim($v), $id ? (int) $id : NULL))
        {
            $this->form_validation->set_message('_unique_username', 'Username already taken.');
            return FALSE;
        }
        return TRUE;
    }

    public function _unique_email($v, $id)
    {
        if ($this->User_model->email_exists(trim($v), $id ? (int) $id : NULL))
        {
            $this->form_validation->set_message('_unique_email', 'Email already registered.');
            return FALSE;
        }
        return TRUE;
    }
}
