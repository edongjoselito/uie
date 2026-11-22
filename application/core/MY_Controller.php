<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UIEIMS base controller.
 * Loads shared context (current user, settings) and provides render()
 * that wraps views in the admin layout.
 */
class MY_Controller extends CI_Controller
{
    /** @var array|null Current authenticated user context */
    protected $user = NULL;

    /** @var array Shared view data */
    protected $data = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->library('database_bootstrap');
        $loopback = array('127.0.0.1', '::1');
        $local_development = ENVIRONMENT === 'development'
            && in_array($_SERVER['SERVER_ADDR'] ?? '', $loopback, TRUE)
            && in_array($_SERVER['REMOTE_ADDR'] ?? '', $loopback, TRUE);
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try
        {
            $this->database_bootstrap->initialize($this->db, $local_development);
        }
        catch (Throwable $error)
        {
            log_message('error', 'Database setup failed: '.$error->getMessage());
            show_error('Database setup failed. Check the application and database logs.', 503);
        }
        finally
        {
            $this->db->db_debug = $db_debug;
        }
        $this->user = $this->access_control->user();

        $this->data['user']        = $this->user;
        $this->data['page_title']  = 'UIEIMS';
        $this->data['breadcrumbs'] = array();
    }

    /**
     * Render a view inside the main admin layout.
     */
    protected function render($view, $data = array())
    {
        $data = array_merge($this->data, $data);
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('layouts/main', $data);
    }

    /**
     * Render a standalone page (no admin chrome) e.g. login.
     */
    protected function render_blank($view, $data = array())
    {
        $data = array_merge($this->data, $data);
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('layouts/blank', $data);
    }

    /**
     * JSON response helper.
     */
    protected function json($data, $status = 200)
    {
        return $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
    }

    protected function is_ajax()
    {
        return (bool) $this->input->is_ajax_request();
    }
}

/**
 * Authenticated controller - requires an active login session.
 * Subclasses may set $allowed_roles / $required_permission to scope access.
 */
class Admin_Controller extends MY_Controller
{
    /** @var array Role slugs permitted to use this controller (empty = any authenticated user) */
    protected $allowed_roles = array();

    /** @var string|null Permission slug required for every action in this controller */
    protected $required_permission = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->access_control->require_login();

        if ( ! empty($this->allowed_roles))
        {
            $this->access_control->require_role($this->allowed_roles);
        }
        if ($this->required_permission !== NULL)
        {
            $this->access_control->require_permission($this->required_permission);
        }
    }
}

/**
 * Superadmin-only controller shortcut.
 */
class Superadmin_Controller extends Admin_Controller
{
    protected $allowed_roles = array('superadmin');
}
