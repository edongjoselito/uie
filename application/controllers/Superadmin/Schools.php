<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Schools extends Superadmin_Controller
{
    protected $per_page = 15;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('School_model', 'Division_model', 'Region_model'));
    }

    public function index()
    {
        $page = max(0, (int) $this->input->get('page'));
        $filters = array(
            'q'           => trim((string) $this->input->get('q')),
            'region_id'   => (int) $this->input->get('region_id')   ?: NULL,
            'division_id' => (int) $this->input->get('division_id') ?: NULL,
            'status'      => $this->input->get('status')      ?: NULL,
            'school_type' => $this->input->get('school_type') ?: NULL,
        );

        $total = $this->School_model->count_filtered($filters);
        $rows  = $this->School_model->paginate_with_org($this->per_page, $page, $filters);

        $this->data['breadcrumbs'] = array('Super Admin' => site_url('superadmin/dashboard'), 'Schools' => NULL);
        $this->render('superadmin/schools/index', array(
            'page_title' => 'Schools - UIEIMS',
            'rows'       => $rows,
            'filters'    => $filters,
            'regions'    => $this->Region_model->active_options(),
            'divisions'  => $filters['region_id'] ? $this->Division_model->options_by_region($filters['region_id'], FALSE) : $this->Division_model->get_where(array('status' => 'active')),
            'pagination' => ci_pagination(site_url('superadmin/schools'), $total, $this->per_page, 0),
            'total'      => $total,
        ));
    }

    public function create()
    {
        $this->access_control->require_permission('schools.create');
        $this->_form(NULL);
    }

    public function edit($id = 0)
    {
        $this->access_control->require_permission('schools.edit');
        $school = $this->School_model->get((int) $id);
        if ( ! $school) show_404();
        $this->_form($school);
    }

    public function view($id = 0)
    {
        $school = $this->School_model->detail((int) $id);
        if ( ! $school) show_404();

        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Schools'     => site_url('superadmin/schools'),
            $school->name => NULL,
        );
        $this->render('superadmin/schools/view', array(
            'page_title' => $school->name.' - School',
            'school'     => $school,
        ));
    }

    public function toggle($id = 0)
    {
        $this->access_control->require_permission('schools.deactivate');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $school = $this->School_model->get((int) $id);
        if ( ! $school) show_404();

        $new = $school->status === 'active' ? 'inactive' : 'active';
        $this->School_model->update($school->id, array('status' => $new));
        audit_log($new === 'active' ? 'activate' : 'deactivate', 'schools', 'schools', $school->id,
            'School '.$school->name.' set to '.$new, array('status' => $school->status), array('status' => $new));

        $this->session->set_flashdata('success', 'School <b>'.e($school->name).'</b> is now '.$new.'.');
        redirect('superadmin/schools');
    }

    protected function _form($school)
    {
        if ($this->input->method(TRUE) === 'POST')
        {
            $id = $school ? $school->id : NULL;
            $this->form_validation->set_rules('school_id', 'DepEd School ID', 'required|trim|max_length[20]|callback__unique_school_id['.($id ?: '').']');
            $this->form_validation->set_rules('name', 'School Name', 'required|trim|max_length[200]');
            $this->form_validation->set_rules('division_id', 'Division', 'required|integer|callback__division_exists');
            $this->form_validation->set_rules('municipality', 'Municipality/City', 'trim|max_length[120]');
            $this->form_validation->set_rules('barangay', 'Barangay', 'trim|max_length[120]');
            $this->form_validation->set_rules('classification', 'Classification', 'trim|max_length[100]');
            $this->form_validation->set_rules('school_type', 'School Type', 'trim|max_length[50]');

            if ($this->form_validation->run())
            {
                $division = $this->Division_model->get((int) $this->input->post('division_id'));
                $data = array(
                    'school_id'      => trim($this->input->post('school_id', TRUE)),
                    'name'           => trim($this->input->post('name', TRUE)),
                    'division_id'    => $division->id,
                    'region_id'      => $division->region_id,
                    'municipality'   => trim((string) $this->input->post('municipality', TRUE)) ?: NULL,
                    'barangay'       => trim((string) $this->input->post('barangay', TRUE)) ?: NULL,
                    'classification' => trim((string) $this->input->post('classification', TRUE)) ?: NULL,
                    'school_type'    => trim((string) $this->input->post('school_type', TRUE)) ?: NULL,
                );
                if ($school)
                {
                    $this->School_model->update($school->id, $data);
                    audit_log('update', 'schools', 'schools', $school->id, 'Updated school '.$data['name'], (array) $school, $data);
                    $this->session->set_flashdata('success', 'School updated.');
                }
                else
                {
                    $data['status'] = 'active';
                    $new_id = $this->School_model->insert($data);
                    audit_log('create', 'schools', 'schools', $new_id, 'Created school '.$data['name'], NULL, $data);
                    $this->session->set_flashdata('success', 'School created.');
                }
                redirect('superadmin/schools');
            }
        }

        // Pre-select region of the school (or posted division's region)
        $sel_region = NULL;
        if ($school) $sel_region = $school->region_id;
        elseif ($this->input->post('division_id'))
        {
            $d = $this->Division_model->get((int) $this->input->post('division_id'));
            $sel_region = $d ? $d->region_id : NULL;
        }

        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Schools'     => site_url('superadmin/schools'),
            $school ? 'Edit' : 'Add School' => NULL,
        );
        $this->render('superadmin/schools/form', array(
            'page_title'  => ($school ? 'Edit' : 'Add').' School - UIEIMS',
            'school'      => $school,
            'regions'     => $this->Region_model->active_options(),
            'divisions'   => $sel_region ? $this->Division_model->options_by_region($sel_region, FALSE) : array(),
            'sel_region'  => $sel_region,
        ));
    }

    public function _unique_school_id($sid, $id)
    {
        if ($this->School_model->school_id_exists(trim($sid), $id ? (int) $id : NULL))
        {
            $this->form_validation->set_message('_unique_school_id', 'This DepEd School ID is already registered.');
            return FALSE;
        }
        return TRUE;
    }

    public function _division_exists($division_id)
    {
        if ( ! $this->Division_model->get((int) $division_id))
        {
            $this->form_validation->set_message('_division_exists', 'Please select a valid division.');
            return FALSE;
        }
        return TRUE;
    }
}
