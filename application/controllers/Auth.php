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

    public function forgot_password()
    {
        $this->reset_headers();
        $this->load->model('Password_reset_model');
        if ($this->input->method(TRUE) === 'POST')
        {
            $this->form_validation->set_rules('email', 'Email address', 'required|valid_email|max_length[120]');
            if ($this->form_validation->run())
            {
                $email = strtolower(trim((string) $this->input->post('email')));
                $ip_allowed = $this->Password_reset_model->allow_request('request-ip', $this->input->ip_address(), 10);
                $email_allowed = $this->Password_reset_model->allow_request('request-email', $email, 3);
                if ($ip_allowed && $email_allowed)
                {
                    $reset = $this->Password_reset_model->issue($email);
                    if ($reset && ! $this->send_reset_email($reset))
                    {
                        $this->Password_reset_model->revoke($reset['token']);
                        log_message('error', 'Password reset email could not be delivered. Check SMTP configuration.');
                    }
                }
                $this->session->set_flashdata('success', 'If an eligible account matches that email address, a password reset link will be sent. Please check your inbox and spam folder. Links expire in 30 minutes.');
                redirect('forgot-password');
            }
        }
        $this->data['page_title'] = 'Forgot Password - UIEIMS';
        $this->render_blank('auth/password_reset', array('reset_mode' => FALSE));
    }

    public function reset_password()
    {
        $this->reset_headers();
        $this->load->model('Password_reset_model');
        // Keep the email token in the URL fragment so it never enters access logs.
        // JavaScript submits it once with CSRF protection, then clears the fragment.
        $token = $this->session->userdata('password_reset_token');
        $error = NULL;
        if ($this->input->method(TRUE) === 'POST')
        {
            $allowed = $this->Password_reset_model->allow_request('redeem-ip', $this->input->ip_address(), 30);
            if ($this->input->post('action') === 'verify')
            {
                $candidate = $this->input->post('token');
                $this->session->unset_userdata('password_reset_token');
                if ($allowed && $this->Password_reset_model->valid($candidate))
                {
                    $this->session->sess_regenerate(TRUE);
                    $this->session->set_userdata('password_reset_token', $candidate);
                }
                else
                {
                    $this->session->set_flashdata('error', 'This reset link is invalid or has expired. Please request a new link.');
                }
                redirect('reset-password');
            }
            if ( ! $allowed || ! $this->Password_reset_model->valid($token))
            {
                $error = 'This reset link is invalid or has expired. Please request a new link.';
            }
            else
            {
                $this->form_validation->set_rules('new_password', 'New password', 'required|min_length[12]|callback_reset_password_length');
                $this->form_validation->set_rules('confirm_password', 'Confirm password', 'required|matches[new_password]');
                if ($this->form_validation->run())
                {
                    $uid = $this->Password_reset_model->consume($token, (string) $this->input->post('new_password'));
                    if ($uid)
                    {
                        audit_log('password_reset', 'auth', 'users', $uid, 'Password reset using emailed single-use token');
                        $this->access_control->clear_session();
                        redirect('login?password_reset=success');
                    }
                    $error = 'This reset link is invalid or has expired. Please request a new link.';
                }
            }
        }
        $this->data['page_title'] = 'Reset Password - UIEIMS';
        $this->render_blank('auth/password_reset', array(
            'reset_mode' => TRUE,
            'token_valid' => $this->Password_reset_model->valid($token),
            'reset_error' => $error,
        ));
    }

    public function reset_password_length($password)
    {
        if (strlen((string) $password) <= 72) return TRUE;
        $this->form_validation->set_message('reset_password_length', 'New password must be at most 72 bytes.');
        return FALSE;
    }

    private function reset_headers()
    {
        $this->output->set_header('Cache-Control: no-store, private');
        $this->output->set_header('Referrer-Policy: no-referrer');
        $this->output->set_header('X-Frame-Options: DENY');
        $this->output->set_header("Content-Security-Policy: frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    }

    private function send_reset_email(array $reset)
    {
        $this->config->load('password_reset', TRUE);
        $cfg = $this->config->item('password_reset');
        if (empty($cfg['smtp_host']) || ! filter_var($cfg['from_email'], FILTER_VALIDATE_EMAIL)
            || ! in_array($cfg['smtp_crypto'], array('tls', 'ssl'), TRUE)) return FALSE;
        $this->load->library('email');
        $this->email->initialize(array(
            'protocol' => 'smtp', 'smtp_host' => $cfg['smtp_host'],
            'smtp_port' => $cfg['smtp_port'], 'smtp_user' => $cfg['smtp_user'],
            'smtp_pass' => $cfg['smtp_pass'], 'smtp_crypto' => $cfg['smtp_crypto'],
            'smtp_timeout' => 10, 'mailtype' => 'text', 'charset' => 'utf-8',
            'newline' => "\r\n", 'crlf' => "\r\n",
        ));
        // site_url uses the configured base URL, never the incoming Host header.
        $url = site_url('reset-password').'#'.$reset['token'];
        $this->email->from($cfg['from_email'], 'UIEIMS - DepEd Region XI');
        $this->email->to($reset['email']);
        $this->email->subject('Reset your UIEIMS password');
        $this->email->message("A password reset was requested for your UIEIMS account.\n\nOpen this link to choose a new password:\n".$url."\n\nThis link expires in 30 minutes and can be used only once. If you did not request this, ignore this email. Your password has not changed.");
        $sent = $this->email->send(FALSE);
        $this->email->clear(TRUE);
        return $sent;
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

                $fresh = $this->User_model->get($this->user['id']);
                $this->session->set_userdata('uie_password_version', hash('sha256', $fresh->password_hash));
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
