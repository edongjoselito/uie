<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UIEIMS Access Control library.
 *
 * Centralizes authentication state, role/permission checks and
 * organizational scoping (region / division / school) so every
 * protected action enforces server-side authorization.
 */
class Access_control
{
    /** @var CI_Controller */
    protected $CI;

    protected $context_refreshed = FALSE;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    // ------------------------------------------------------------------
    // Session / identity
    // ------------------------------------------------------------------

    public function is_logged_in()
    {
        $uid = $this->CI->session->userdata('uie_uid');
        if ( ! $uid) return FALSE;
        $row = $this->CI->db->select('password_hash, status')->where('id', $uid)->get('users')->row_array();
        $version = $this->CI->session->userdata('uie_password_version');
        if ( ! $row || $row['status'] !== 'active' || ! is_string($version)
            || ! hash_equals(hash('sha256', $row['password_hash']), $version))
        {
            $this->CI->session->unset_userdata(array('uie_uid', 'uie_user', 'uie_password_version'));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Returns the session-cached user context array or NULL.
     * Keys: id, username, email, full_name, roles, role_names,
     * permissions, region_id, division_id, school_id, level.
     */
    public function user()
    {
        if ( ! $this->is_logged_in())
        {
            return NULL;
        }
        if ( ! $this->context_refreshed)
        {
            $row = $this->CI->db->where('id', $this->CI->session->userdata('uie_uid'))->get('users')->row_array();
            $context = $this->build_context($row);
            $this->CI->session->set_userdata('uie_user', $context);
            $this->context_refreshed = TRUE;
        }
        return $this->CI->session->userdata('uie_user');
    }

    /**
     * Build the session context after a successful authentication.
     */
    public function establish_session(array $user_row)
    {
        $CI = $this->CI;
        $context = $this->build_context($user_row);
        $CI->session->sess_regenerate(TRUE);
        $CI->session->set_userdata('uie_uid', (int) $user_row['id']);
        $CI->session->set_userdata('uie_password_version', hash('sha256', $user_row['password_hash']));
        $CI->session->set_userdata('uie_user', $context);
        $this->context_refreshed = TRUE;
        return $context;
    }

    /** Refresh database role grants and school assignment once per request. */
    private function build_context(array $user_row)
    {
        $CI = $this->CI;
        $CI->load->model('User_model');

        $roles       = $CI->User_model->get_user_roles($user_row['id']);      // rows with slug,name,level
        $permissions = $CI->User_model->get_user_permissions($user_row['id']); // flat slug list
        $assignment  = $CI->User_model->get_primary_assignment($user_row['id']);

        $role_slugs  = array_column($roles, 'slug');
        $role_names  = array_column($roles, 'name');

        // Highest organizational level across roles
        $level = 'school';
        $order = array('system' => 4, 'region' => 3, 'division' => 2, 'school' => 1);
        foreach ($roles as $r)
        {
            if (isset($order[$r['level']]) && $order[$r['level']] > $order[$level])
            {
                $level = $r['level'];
            }
        }
        if (in_array('superadmin', $role_slugs))
        {
            $level = 'system';
        }

        $context = array(
            'id'           => (int) $user_row['id'],
            'username'     => $user_row['username'],
            'email'        => $user_row['email'],
            'full_name'    => trim($user_row['first_name'].' '.$user_row['last_name']),
            'first_name'   => $user_row['first_name'],
            'last_name'    => $user_row['last_name'],
            'roles'        => $role_slugs,
            'role_names'   => $role_names,
            'permissions'  => $permissions,
            'level'        => $level,
            'region_id'    => isset($assignment['region_id'])   ? (int) $assignment['region_id']   : NULL,
            'division_id'  => isset($assignment['division_id']) ? (int) $assignment['division_id'] : NULL,
            'school_id'    => isset($assignment['school_id'])   ? (int) $assignment['school_id']   : NULL,
            'must_change_password' => (int) $user_row['must_change_password'],
        );

        return $context;
    }

    public function clear_session()
    {
        $this->CI->session->unset_userdata(array('uie_uid', 'uie_user', 'uie_password_version'));
        $this->CI->session->sess_destroy();
    }

    // ------------------------------------------------------------------
    // Requirement gates (used by controllers)
    // ------------------------------------------------------------------

    public function require_login()
    {
        if ( ! $this->is_logged_in())
        {
            if ($this->CI->input->is_ajax_request())
            {
                $this->CI->output
                    ->set_status_header(401)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('error' => 'Authentication required.')))
                    ->_display();
                exit;
            }
            $this->CI->session->set_userdata('uie_redirect', uri_string());
            redirect('login');
        }
    }

    /**
     * @param string|array $slugs
     */
    public function require_role($slugs)
    {
        $this->require_login();
        if ( ! $this->has_role($slugs))
        {
            $this->deny('You do not have the required role to access this page.');
        }
    }

    public function require_permission($slug)
    {
        $this->require_login();
        if ( ! $this->has_permission($slug))
        {
            $this->deny('You do not have permission to perform this action.');
        }
    }

    // ------------------------------------------------------------------
    // Checks
    // ------------------------------------------------------------------

    /**
     * @param string|array $slugs - TRUE if the user holds ANY of the roles.
     */
    public function has_role($slugs)
    {
        $user = $this->user();
        if ($user === NULL) return FALSE;
        if (in_array('superadmin', $user['roles'])) return TRUE;
        return (bool) array_intersect((array) $slugs, $user['roles']);
    }

    public function has_permission($slug)
    {
        $user = $this->user();
        if ($user === NULL) return FALSE;
        if (in_array('superadmin', $user['roles'])) return TRUE;
        return in_array($slug, $user['permissions'], TRUE);
    }

    // can() alias used in views
    public function can($slug)
    {
        return $this->has_permission($slug);
    }

    public function is_superadmin()
    {
        $user = $this->user();
        return $user !== NULL && in_array('superadmin', $user['roles']);
    }

    // ------------------------------------------------------------------
    // Organizational scope (server-side data isolation)
    // ------------------------------------------------------------------

    /** Region scope: NULL means unrestricted (superadmin). */
    public function scope_region_id()
    {
        $user = $this->user();
        if ($user === NULL || $this->is_superadmin()) return NULL;
        return $user['region_id'];
    }

    public function scope_division_id()
    {
        $user = $this->user();
        if ($user === NULL || $this->is_superadmin()) return NULL;
        return $user['division_id'];
    }

    public function scope_school_id()
    {
        $user = $this->user();
        if ($user === NULL || $this->is_superadmin()) return NULL;
        return $user['school_id'];
    }

    /**
     * Verify a record (with region_id/division_id/school_id fields)
     * falls inside the authenticated user's scope. Returns TRUE/FALSE.
     */
    public function record_in_scope($region_id = NULL, $division_id = NULL, $school_id = NULL)
    {
        if ($this->is_superadmin()) return TRUE;

        $sr = $this->scope_region_id();
        if ($sr !== NULL)
        {
            if ($region_id !== NULL && (int) $region_id !== (int) $sr) return FALSE;
            return TRUE; // region users see all divisions/schools in region
        }
        $sd = $this->scope_division_id();
        if ($sd !== NULL)
        {
            if ($division_id !== NULL && (int) $division_id !== (int) $sd) return FALSE;
            return TRUE;
        }
        $ss = $this->scope_school_id();
        if ($ss !== NULL)
        {
            if ($school_id !== NULL && (int) $school_id !== (int) $ss) return FALSE;
            return TRUE;
        }
        // User has roles but no organizational assignment -> deny by default
        return FALSE;
    }

    // ------------------------------------------------------------------
    // Access denied
    // ------------------------------------------------------------------

    public function deny($message = 'Access denied.')
    {
        if ($this->CI->input->is_ajax_request())
        {
            $this->CI->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('error' => $message)))
                ->_display();
            exit;
        }
        show_error($message, 403, 'Forbidden');
    }
}
