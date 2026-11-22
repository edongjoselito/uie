<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UIEIMS Authentication - session based, bcrypt passwords,
 * account lockout on repeated failures, audit logging.
 */
class Auth extends MY_Controller
{
    public function index()
    {
        redirect('login');
    }

    public function login()
    {
        if ($this->access_control->is_logged_in())
        {
            redirect(level_dashboard_url($this->user));
        }

        if ($this->input->method(TRUE) === 'POST')
        {
            $this->form_validation->set_rules('username', 'Username', 'required|trim|max_length[120]');
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run())
            {
                $username = $this->input->post('username', TRUE);
                $password = (string) $this->input->post('password');

                $this->load->model('User_model');
                $user = $this->User_model->find_for_login($username);
                $this->session->set_userdata('uie_attempt_user', $username);

                if ($user)
                {
                    // Lockout check
                    if ($user['status'] === 'locked' && $user['locked_until'] && strtotime($user['locked_until']) > time())
                    {
                        $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
                        $this->session->set_flashdata('error', 'Account is temporarily locked. Try again in '.$mins.' minute(s).');
                        audit_log('login_locked', 'auth', 'users', $user['id'], 'Locked account login attempt');
                        redirect('login');
                    }
                    if ($user['status'] !== 'active' && $user['status'] !== 'locked')
                    {
                        $this->session->set_flashdata('error', 'This account is deactivated. Contact your administrator.');
                        audit_log('login_denied', 'auth', 'users', $user['id'], 'Inactive account login attempt');
                        redirect('login');
                    }
                    if (password_verify($password, $user['password_hash']))
                    {
                        if ($user['status'] === 'locked')
                        {
                            // lock expired -> reactivate
                            $this->User_model->unlock($user['id']);
                        }
                        $this->User_model->record_successful_login($user['id']);
                        $context = $this->access_control->establish_session($user);
                        audit_log('login', 'auth', 'users', $user['id'], 'User logged in');

                        if ($context['must_change_password'])
                        {
                            redirect('change-password');
                        }
                        $dest = $this->session->userdata('uie_redirect');
                        $this->session->unset_userdata('uie_redirect');
                        redirect($dest ? $dest : level_dashboard_url($context));
                    }
                    else
                    {
                        $max  = (int) app_setting('login_max_attempts', 5);
                        $mins = (int) app_setting('login_lockout_minutes', 15);
                        $result = $this->User_model->record_failed_login($user['id'], $max, $mins);
                        audit_log('login_failed', 'auth', 'users', $user['id'], 'Invalid password');
                        $this->session->set_flashdata('error',
                            $result === 'locked'
                                ? 'Too many failed attempts. Account locked for '.$mins.' minutes.'
                                : 'Invalid username or password.');
                        redirect('login');
                    }
                }
                else
                {
                    audit_log('login_failed', 'auth', 'users', NULL, 'Unknown username: '.$username);
                    $this->session->set_flashdata('error', 'Invalid username or password.');
                    redirect('login');
                }
            }
        }

        $this->data['page_title'] = 'Sign In - UIEIMS';
        $this->render_blank('auth/login');
    }

    public function logout()
    {
        $user = $this->user;
        if ($user)
        {
            audit_log('logout', 'auth', 'users', $user['id'], 'User logged out');
        }
        $this->access_control->clear_session();
        redirect('login');
    }

    /**
     * Authenticated users change their own password.
     */
    public function change_password()
    {
        $this->access_control->require_login();

        if ($this->input->method(TRUE) === 'POST')
        {
            $this->form_validation->set_rules('current_password', 'Current Password', 'required');
            $this->form_validation->set_rules('new_password', 'New Password', 'required|min_length[8]|max_length[72]');
            $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[new_password]');

            if ($this->form_validation->run())
            {
                $this->load->model('User_model');
                $row = $this->User_model->get($this->user['id']);

                if ( ! password_verify((string) $this->input->post('current_password'), $row->password_hash))
                {
                    $this->session->set_flashdata('error', 'Current password is incorrect.');
                    redirect('change-password');
                }

                $this->User_model->update($this->user['id'], array(
                    'password_hash'        => password_hash((string) $this->input->post('new_password'), PASSWORD_BCRYPT),
                    'password_changed_at'  => date('Y-m-d H:i:s'),
                    'must_change_password' => 0,
                ));
                audit_log('password_change', 'auth', 'users', $this->user['id'], 'User changed own password');

                // refresh session flag
                $ctx = $this->session->userdata('uie_user');
                $ctx['must_change_password'] = 0;
                $this->session->set_userdata('uie_user', $ctx);

                $this->session->set_flashdata('success', 'Password updated successfully.');
                redirect(level_dashboard_url($ctx));
            }
        }

        $this->data['page_title'] = 'Change Password - UIEIMS';
        $this->render_blank('auth/change_password');
    }
}
