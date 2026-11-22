<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UIEIMS view + domain helpers.
 */

if ( ! function_exists('e'))
{
    /** Escape output for HTML context. */
    function e($str)
    {
        return html_escape($str === NULL ? '' : $str);
    }
}

if ( ! function_exists('app_setting'))
{
    /** Fetch a system setting with a default. Cached per request. */
    function app_setting($key, $default = NULL)
    {
        static $settings = NULL;
        if ($settings === NULL)
        {
            $CI =& get_instance();
            $settings = array();
            if ($CI->db->table_exists('system_settings'))
            {
                foreach ($CI->db->get('system_settings')->result() as $row)
                {
                    $settings[$row->setting_key] = $row->setting_value;
                }
            }
        }
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}

if ( ! function_exists('active_sy'))
{
    /** Active school year row (object) or NULL. */
    function active_sy()
    {
        static $sy = FALSE;
        if ($sy === FALSE)
        {
            $CI =& get_instance();
            $sy = $CI->db->get_where('school_years', array('is_active' => 1))->row();
        }
        return $sy;
    }
}

if ( ! function_exists('learner_age'))
{
    /** Age from a Y-m-d date of birth. */
    function learner_age($dob, $as_of = NULL)
    {
        if (empty($dob) || $dob === '0000-00-00') return NULL;
        try
        {
            $birth = new DateTime($dob);
            $ref   = $as_of ? new DateTime($as_of) : new DateTime('today');
            return $birth->diff($ref)->y;
        }
        catch (Exception $ex)
        {
            return NULL;
        }
    }
}

if ( ! function_exists('status_badge'))
{
    /** Bootstrap badge for an entity status. */
    function status_badge($status)
    {
        $map = array(
            'active'           => 'success',
            'inactive'         => 'secondary',
            'locked'           => 'danger',
            'archived'         => 'dark',
            'draft'            => 'secondary',
            'for_review'       => 'info',
            'submitted'        => 'primary',
            'under_validation' => 'warning',
            'returned'         => 'danger',
            'resubmitted'      => 'info',
            'validated'        => 'success',
            'enabled'          => 'success',
            'disabled'         => 'secondary',
            'open'             => 'success',
            'closed'           => 'dark',
        );
        $class = isset($map[$status]) ? $map[$status] : 'secondary';
        return '<span class="badge text-bg-'.$class.'">'.e(ucwords(str_replace('_', ' ', $status))).'</span>';
    }
}

if ( ! function_exists('programs'))
{
    /**
     * Canonical identity of the three UIEIMS program pillars.
     * Each entry: code, name, short, color, icon, tagline.
     */
    function programs($code = NULL)
    {
        $programs = array(
            'iped' => array(
                'code'    => 'iped',
                'name'    => 'Indigenous Peoples Education',
                'short'   => 'IPEd',
                'color'   => '#17795c',
                'icon'    => 'bi-tree',
                'tagline' => 'Culturally responsive education for Indigenous Cultural Communities',
            ),
            'madrasah' => array(
                'code'    => 'madrasah',
                'name'    => 'Madrasah Education Program',
                'short'   => 'Madrasah',
                'color'   => '#b8860b',
                'icon'    => 'bi-moon-stars',
                'tagline' => 'Arabic Language &amp; Islamic Values Education (ALIVE)',
            ),
            'sned' => array(
                'code'    => 'sned',
                'name'    => 'Special Needs Education',
                'short'   => 'SNED',
                'color'   => '#6f42c1',
                'icon'    => 'bi-universal-access',
                'tagline' => 'Inclusive education for learners with disabilities',
            ),
            'spfl' => array(
                'code'    => 'spfl',
                'name'    => 'Special Program in Foreign Language',
                'short'   => 'SPFL',
                'color'   => '#c0392b',
                'icon'    => 'bi-translate',
                'tagline' => 'Foreign language proficiency for global competence',
            ),
        );
        if ($code === NULL) return $programs;
        return isset($programs[$code]) ? $programs[$code] : NULL;
    }
}

if ( ! function_exists('program_badge'))
{
    /** Colored badge for a program code ('iped'|'madrasah'|'sned'). */
    function program_badge($code, $with_icon = TRUE)
    {
        $p = programs($code);
        if ( ! $p) return '';
        $icon = $with_icon ? '<i class="bi '.$p['icon'].' me-1"></i>' : '';
        return '<span class="badge badge-'.$p['code'].'">'.$icon.e($p['short']).'</span>';
    }
}

if ( ! function_exists('program_dot'))
{
    /** Small colored square marker for a program (used in sidebar/nav). */
    function program_dot($code)
    {
        $p = programs($code);
        if ( ! $p) return '';
        return '<span class="program-dot" style="background:'.$p['color'].'" title="'.e($p['name']).'"></span>';
    }
}

if ( ! function_exists('nav_active'))
{
    /** Return 'active' when the first URI segment matches the menu key. */
    function nav_active($key, $segment = 1)
    {
        $CI =& get_instance();
        return $CI->uri->segment($segment) === $key ? 'active' : '';
    }
}

if ( ! function_exists('flash_html'))
{
    /** Render flash messages (success / error / warning / info). */
    function flash_html()
    {
        $CI =& get_instance();
        $out = '';
        foreach (array('success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info') as $key => $class)
        {
            $msg = $CI->session->flashdata($key);
            if ($msg)
            {
                $out .= '<div class="alert alert-'.$class.' alert-dismissible fade show" role="alert">'
                      . $msg
                      . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
        return $out;
    }
}

if ( ! function_exists('ci_pagination'))
{
    /**
     * Build CI pagination links for a list page.
     * @return string HTML links
     */
    function ci_pagination($base_url, $total_rows, $per_page, $uri_segment)
    {
        $CI =& get_instance();
        $CI->load->library('pagination');
        $config = array(
            'base_url'            => $base_url,
            'total_rows'          => $total_rows,
            'per_page'            => $per_page,
            'page_query_string'   => TRUE,
            'query_string_segment'=> 'page',
            'reuse_query_string'  => TRUE,
            'full_tag_open'       => '<nav><ul class="pagination pagination-sm justify-content-end mb-0">',
            'full_tag_close'      => '</ul></nav>',
            'first_link'          => '&laquo;',
            'first_tag_open'      => '<li class="page-item">',
            'first_tag_close'     => '</li>',
            'last_link'           => '&raquo;',
            'last_tag_open'       => '<li class="page-item">',
            'last_tag_close'      => '</li>',
            'next_link'           => '&rsaquo;',
            'next_tag_open'       => '<li class="page-item">',
            'next_tag_close'      => '</li>',
            'prev_link'           => '&lsaquo;',
            'prev_tag_open'       => '<li class="page-item">',
            'prev_tag_close'      => '</li>',
            'cur_tag_open'        => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close'       => '</span></li>',
            'num_tag_open'        => '<li class="page-item">',
            'num_tag_close'       => '</li>',
            'attributes'          => array('class' => 'page-link'),
        );
        $CI->pagination->initialize($config);
        return $CI->pagination->create_links();
    }
}

if ( ! function_exists('audit_log'))
{
    /**
     * Write an audit log entry.
     * audit_log('create', 'regions', 'regions', 5, 'Created region X', $old, $new)
     */
    function audit_log($action, $module = NULL, $table = NULL, $record_id = NULL, $description = NULL, $old = NULL, $new = NULL)
    {
        $CI =& get_instance();
        $CI->load->model('Audit_model');
        return $CI->Audit_model->log($action, $module, $table, $record_id, $description, $old, $new);
    }
}

if ( ! function_exists('level_dashboard_url'))
{
    /** Home URL for a given user context array. */
    function level_dashboard_url($user)
    {
        if ( ! $user) return site_url('login');
        switch ($user['level'])
        {
            case 'system':   return site_url('superadmin/dashboard');
            case 'region':   return site_url('dashboard');
            case 'division': return site_url('dashboard');
            case 'school':   return site_url('dashboard');
            default:         return site_url('dashboard');
        }
    }
}

if ( ! function_exists('csrf_json'))
{
    /** CSRF token as a JS-ready array for AJAX posts. */
    function csrf_json()
    {
        $CI =& get_instance();
        return array(
            'name' => $CI->security->get_csrf_token_name(),
            'hash' => $CI->security->get_csrf_hash(),
        );
    }
}
