<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Regions extends Superadmin_Controller
{
    protected $per_page = 15;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Region_model', 'Division_model'));
    }

    public function index()
    {
        $q      = trim((string) $this->input->get('q'));
        $status = $this->input->get('status');
        $page   = max(0, (int) $this->input->get('page'));

        $filters = array('q' => $q, 'status' => $status);
        $all     = $this->Region_model->with_stats($filters);

        // Simple array slice - region count is small (<= 20)
        $total  = count($all);
        $rows   = array_slice($all, $page, $this->per_page);

        $this->data['breadcrumbs'] = array('Super Admin' => site_url('superadmin/dashboard'), 'Regions' => NULL);
        $this->render('superadmin/regions/index', array(
            'page_title' => 'Regions - UIEIMS',
            'rows'       => $rows,
            'filters'    => $filters,
            'pagination' => ci_pagination(site_url('superadmin/regions'), $total, $this->per_page, 0),
            'total'      => $total,
        ));
    }

    public function create()
    {
        $this->access_control->require_permission('regions.create');
        $this->_form(NULL);
    }

    public function edit($id = 0)
    {
        $this->access_control->require_permission('regions.edit');
        $region = $this->Region_model->get((int) $id);
        if ( ! $region) show_404();
        $this->_form($region);
    }

    public function view($id = 0)
    {
        $region = $this->Region_model->detail((int) $id);
        if ( ! $region) show_404();

        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Regions'     => site_url('superadmin/regions'),
            $region->name => NULL,
        );
        $this->render('superadmin/regions/view', array(
            'page_title' => $region->name.' - Region',
            'region'     => $region,
            'divisions'  => $this->Division_model->paginate_with_region(200, 0, array('region_id' => $region->id)),
        ));
    }

    public function toggle($id = 0)
    {
        $this->access_control->require_permission('regions.deactivate');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);

        $region = $this->Region_model->get((int) $id);
        if ( ! $region) show_404();

        $new = $region->status === 'active' ? 'inactive' : 'active';
        $this->Region_model->update($region->id, array('status' => $new));
        audit_log($new === 'active' ? 'activate' : 'deactivate', 'regions', 'regions', $region->id,
            'Region '.$region->name.' set to '.$new, array('status' => $region->status), array('status' => $new));

        $this->session->set_flashdata('success', 'Region <b>'.e($region->name).'</b> is now '.$new.'.');
        redirect('superadmin/regions');
    }

    protected function _form($region)
    {
        if ($this->input->method(TRUE) === 'POST')
        {
            $id = $region ? $region->id : NULL;
            $this->form_validation->set_rules('code', 'Region Code', 'required|trim|max_length[20]|callback__unique_region_code['.($id ?: '').']');
            $this->form_validation->set_rules('name', 'Region Name', 'required|trim|max_length[150]');
            $this->form_validation->set_rules('description', 'Description', 'trim|max_length[1000]');

            if ($this->form_validation->run())
            {
                $data = array(
                    'code'        => strtoupper(trim($this->input->post('code', TRUE))),
                    'name'        => trim($this->input->post('name', TRUE)),
                    'description' => trim((string) $this->input->post('description', TRUE)),
                );
                if ($region)
                {
                    $this->Region_model->update($region->id, $data);
                    audit_log('update', 'regions', 'regions', $region->id, 'Updated region '.$data['name'], (array) $region, $data);
                    $this->session->set_flashdata('success', 'Region updated.');
                }
                else
                {
                    $data['status'] = 'active';
                    $new_id = $this->Region_model->insert($data);
                    audit_log('create', 'regions', 'regions', $new_id, 'Created region '.$data['name'], NULL, $data);
                    $this->session->set_flashdata('success', 'Region created.');
                }
                redirect('superadmin/regions');
            }
        }

        $this->data['breadcrumbs'] = array(
            'Super Admin' => site_url('superadmin/dashboard'),
            'Regions'     => site_url('superadmin/regions'),
            $region ? 'Edit' : 'Add Region' => NULL,
        );
        $this->render('superadmin/regions/form', array(
            'page_title' => ($region ? 'Edit' : 'Add').' Region - UIEIMS',
            'region'     => $region,
        ));
    }

    // validation callback
    public function _unique_region_code($code, $id)
    {
        if ($this->Region_model->exists(array('code' => strtoupper(trim($code))), $id ? (int) $id : NULL))
        {
            $this->form_validation->set_message('_unique_region_code', 'The {field} is already in use.');
            return FALSE;
        }
        return TRUE;
    }
}
