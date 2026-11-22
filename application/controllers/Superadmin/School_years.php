<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class School_years extends Superadmin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('School_year_model');
    }

    public function index()
    {
        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'School Years' => NULL,
        );
        $this->render('superadmin/school_years/index', array(
            'page_title' => 'School Years - UIEIMS',
            'rows'       => $this->School_year_model->options(),
        ));
    }

    public function create()
    {
        $this->access_control->require_permission('school_years.manage');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $this->form_validation->set_rules('year_label', 'School Year', 'required|trim|regex_match[/^\d{4}-\d{4}$/]|callback__unique_sy');
        if ($this->form_validation->run())
        {
            $label = trim($this->input->post('year_label', TRUE));
            list($start, $end) = array_map('intval', explode('-', $label));
            if ($end !== $start + 1)
            {
                $this->session->set_flashdata('error', 'Invalid school year range (must be consecutive years).');
                redirect('superadmin/school-years');
            }
            $id = $this->School_year_model->insert(array(
                'year_label' => $label,
                'start_year' => $start,
                'end_year'   => $end,
                'is_active'  => 0,
            ));
            audit_log('create', 'school_years', 'school_years', $id, 'Added school year '.$label);
            $this->session->set_flashdata('success', 'School year <b>'.e($label).'</b> added.');
        }
        redirect('superadmin/school-years');
    }

    public function set_active($id = 0)
    {
        $this->access_control->require_permission('school_years.manage');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $sy = $this->School_year_model->get((int) $id);
        if ( ! $sy) show_404();

        $this->School_year_model->set_active($sy->id);
        audit_log('set_active', 'school_years', 'school_years', $sy->id, 'Set active school year to '.$sy->year_label);
        $this->session->set_flashdata('success', 'Active school year is now <b>'.e($sy->year_label).'</b>.');
        redirect('superadmin/school-years');
    }

    public function toggle_status($id = 0)
    {
        $this->access_control->require_permission('school_years.manage');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $sy = $this->School_year_model->get((int) $id);
        if ( ! $sy) show_404();

        $new = $sy->status === 'open' ? 'closed' : 'open';
        $this->School_year_model->update($sy->id, array('status' => $new));
        audit_log($new === 'open' ? 'reopen' : 'close', 'school_years', 'school_years', $sy->id, 'School year '.$sy->year_label.' '.$new);
        $this->session->set_flashdata('success', 'School year <b>'.e($sy->year_label).'</b> is now '.$new.'.');
        redirect('superadmin/school-years');
    }

    public function _unique_sy($label)
    {
        if ($this->School_year_model->label_exists(trim($label)))
        {
            $this->form_validation->set_message('_unique_sy', 'This school year already exists.');
            return FALSE;
        }
        return TRUE;
    }
}
