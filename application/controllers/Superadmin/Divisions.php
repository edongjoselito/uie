<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Divisions extends Superadmin_Controller
{
    protected $per_page = 15;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Division_model', 'Region_model', 'School_model'));
    }

    public function index()
    {
        $page = max(0, (int) $this->input->get('page'));
        $filters = array(
            'q'         => trim((string) $this->input->get('q')),
            'region_id' => (int) $this->input->get('region_id') ?: NULL,
            'status'    => $this->input->get('status') ?: NULL,
        );

        $total = $this->Division_model->count_filtered($filters);
        $rows  = $this->Division_model->paginate_with_region($this->per_page, $page, $filters);

        $this->data['breadcrumbs'] = array('Super Admin' => site_url('superadmin/dashboard'), 'Divisions' => NULL);
        $this->render('superadmin/divisions/index', array(
            'page_title' => 'Schools Divisions - UIEIMS',
            'rows'       => $rows,
            'filters'    => $filters,
            'regions'    => $this->Region_model->active_options(),
            'pagination' => ci_pagination(site_url('superadmin/divisions'), $total, $this->per_page, 0),
            'total'      => $total,
        ));
    }

    public function create()
    {
        $this->access_control->require_permission('divisions.create');
        $this->_form(NULL);
    }

    public function edit($id = 0)
    {
        $this->access_control->require_permission('divisions.edit');
        $division = $this->Division_model->get((int) $id);
        if ( ! $division) show_404();
        $this->_form($division);
    }

    public function view($id = 0)
    {
        $division = $this->Division_model->detail((int) $id);
        if ( ! $division) show_404();

        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Divisions'   => site_url('superadmin/divisions'),
            $division->name => NULL,
        );
        $this->render('superadmin/divisions/view', array(
            'page_title' => $division->name.' - Division',
            'division'   => $division,
            'schools'    => $this->School_model->paginate_with_org(200, 0, array('division_id' => $division->id)),
        ));
    }

    public function toggle($id = 0)
    {
        $this->access_control->require_permission('divisions.deactivate');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $division = $this->Division_model->get((int) $id);
        if ( ! $division) show_404();

        $new = $division->status === 'active' ? 'inactive' : 'active';
        $this->Division_model->update($division->id, array('status' => $new));
        audit_log($new === 'active' ? 'activate' : 'deactivate', 'divisions', 'divisions', $division->id,
            'Division '.$division->name.' set to '.$new, array('status' => $division->status), array('status' => $new));

        $this->session->set_flashdata('success', 'Division <b>'.e($division->name).'</b> is now '.$new.'.');
        redirect('superadmin/divisions');
    }

    protected function _form($division)
    {
        if ($this->input->method(TRUE) === 'POST')
        {
            $id = $division ? $division->id : NULL;
            $this->form_validation->set_rules('code', 'Division Code', 'required|trim|max_length[20]|callback__unique_division_code['.($id ?: '').']');
            $this->form_validation->set_rules('name', 'Division Name', 'required|trim|max_length[150]');
            $this->form_validation->set_rules('region_id', 'Region', 'required|integer|callback__region_exists');

            if ($this->form_validation->run())
            {
                $data = array(
                    'code'      => strtoupper(trim($this->input->post('code', TRUE))),
                    'name'      => trim($this->input->post('name', TRUE)),
                    'region_id' => (int) $this->input->post('region_id'),
                );
                if ($division)
                {
                    $this->Division_model->update($division->id, $data);
                    // Keep schools' denormalized region_id consistent
                    $this->db->where('division_id', $division->id)
                        ->update('schools', array('region_id' => $data['region_id']));
                    audit_log('update', 'divisions', 'divisions', $division->id, 'Updated division '.$data['name'], (array) $division, $data);
                    $this->session->set_flashdata('success', 'Division updated.');
                }
                else
                {
                    $data['status'] = 'active';
                    $new_id = $this->Division_model->insert($data);
                    audit_log('create', 'divisions', 'divisions', $new_id, 'Created division '.$data['name'], NULL, $data);
                    $this->session->set_flashdata('success', 'Division created.');
                }
                redirect('superadmin/divisions');
            }
        }

        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Divisions'   => site_url('superadmin/divisions'),
            $division ? 'Edit' : 'Add Division' => NULL,
        );
        $this->render('superadmin/divisions/form', array(
            'page_title' => ($division ? 'Edit' : 'Add').' Division - UIEIMS',
            'division'   => $division,
            'regions'    => $this->Region_model->active_options(),
        ));
    }

    public function _unique_division_code($code, $id)
    {
        if ($this->Division_model->exists(array('code' => strtoupper(trim($code))), $id ? (int) $id : NULL))
        {
            $this->form_validation->set_message('_unique_division_code', 'The {field} is already in use.');
            return FALSE;
        }
        return TRUE;
    }

    public function _region_exists($region_id)
    {
        if ( ! $this->Region_model->get((int) $region_id))
        {
            $this->form_validation->set_message('_region_exists', 'Please select a valid region.');
            return FALSE;
        }
        return TRUE;
    }
}
