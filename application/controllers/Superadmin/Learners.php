<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Centralized Learner Registry (Phase 1).
 * Manages the common learner identity + current enrollment record.
 * Program-specific profiles (IPEd/Madrasah/SNED) attach in later phases.
 */
class Learners extends Superadmin_Controller
{
    protected $per_page = 15;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Learner_model', 'School_model', 'Division_model', 'Region_model', 'School_year_model'));
    }

    public function index()
    {
        $page = max(0, (int) $this->input->get('page'));
        $filters = array(
            'q'              => trim((string) $this->input->get('q')),
            'region_id'      => (int) $this->input->get('region_id') ?: NULL,
            'division_id'    => (int) $this->input->get('division_id') ?: NULL,
            'school_id'      => (int) $this->input->get('school_id') ?: NULL,
            'school_year_id' => (int) $this->input->get('school_year_id') ?: NULL,
            'sex'            => $this->input->get('sex') ?: NULL,
            'status'         => $this->input->get('status') ?: 'active',
            'scope'          => array(), // superadmin: unrestricted
        );

        $total = $this->Learner_model->count_scoped($filters);
        $rows  = $this->Learner_model->paginate_scoped($this->per_page, $page, $filters);

        $this->data['breadcrumbs'] = array('Super Admin' => site_url('superadmin/dashboard'), 'Learner Registry' => NULL);
        $this->render('superadmin/learners/index', array(
            'page_title'   => 'Learner Registry - UIEIMS',
            'rows'         => $rows,
            'filters'      => $filters,
            'regions'      => $this->Region_model->active_options(),
            'divisions'    => $filters['region_id'] ? $this->Division_model->options_by_region($filters['region_id'], FALSE) : array(),
            'schools'      => $filters['division_id'] ? $this->School_model->options_by_division($filters['division_id'], FALSE) : array(),
            'school_years' => $this->School_year_model->options(),
            'pagination'   => ci_pagination(site_url('superadmin/learners'), $total, $this->per_page, 0),
            'total'        => $total,
        ));
    }

    public function create()
    {
        $this->access_control->require_permission('learners.create');
        $this->_form(NULL);
    }

    public function edit($id = 0)
    {
        $this->access_control->require_permission('learners.edit');
        $learner = $this->Learner_model->get((int) $id);
        if ( ! $learner) show_404();
        $this->_form($learner);
    }

    public function view($id = 0)
    {
        $learner = $this->Learner_model->detail((int) $id);
        if ( ! $learner) show_404();

        $this->data['breadcrumbs'] = array(
            'Super Admin'      => site_url('superadmin/dashboard'),
            'Learner Registry' => site_url('superadmin/learners'),
            $learner->first_name.' '.$learner->last_name => NULL,
        );
        $this->render('superadmin/learners/view', array(
            'page_title' => 'Learner Profile - UIEIMS',
            'learner'    => $learner,
        ));
    }

    public function archive($id = 0)
    {
        $this->access_control->require_permission('learners.archive');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);
        $learner = $this->Learner_model->get((int) $id);
        if ( ! $learner) show_404();

        $this->Learner_model->archive($learner->id);
        audit_log('archive', 'learners', 'learners', $learner->id, 'Archived learner '.$learner->last_name.', '.$learner->first_name);
        $this->session->set_flashdata('success', 'Learner record archived.');
        redirect('superadmin/learners');
    }

    public function restore($id = 0)
    {
        $this->access_control->require_permission('learners.restore');
        if ($this->input->method(TRUE) !== 'POST') show_error('Method not allowed', 405);
        $learner = $this->Learner_model->get((int) $id);
        if ( ! $learner) show_404();

        $this->Learner_model->restore($learner->id);
        audit_log('restore', 'learners', 'learners', $learner->id, 'Restored learner '.$learner->last_name.', '.$learner->first_name);
        $this->session->set_flashdata('success', 'Learner record restored.');
        redirect('superadmin/learners?status=archived');
    }

    protected function _form($learner)
    {
        $enrollment = $learner ? $this->Learner_model->get_enrollment($learner->id) : NULL;

        if ($this->input->method(TRUE) === 'POST')
        {
            $id = $learner ? $learner->id : NULL;
            $this->form_validation->set_rules('lrn', 'LRN', 'trim|max_length[12]|callback__unique_lrn['.($id ?: '').']');
            $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|max_length[80]');
            $this->form_validation->set_rules('first_name', 'First Name', 'required|trim|max_length[80]');
            $this->form_validation->set_rules('middle_name', 'Middle Name', 'trim|max_length[80]');
            $this->form_validation->set_rules('extension_name', 'Extension Name', 'trim|max_length[20]');
            $this->form_validation->set_rules('sex', 'Sex', 'required|in_list[Male,Female]');
            $this->form_validation->set_rules('date_of_birth', 'Date of Birth', 'trim|callback__valid_date');
            $this->form_validation->set_rules('place_of_birth', 'Place of Birth', 'trim|max_length[200]');
            $this->form_validation->set_rules('address', 'Address', 'trim|max_length[255]');
            $this->form_validation->set_rules('barangay', 'Barangay', 'trim|max_length[120]');
            $this->form_validation->set_rules('municipality', 'Municipality', 'trim|max_length[120]');
            $this->form_validation->set_rules('province', 'Province', 'trim|max_length[120]');
            $this->form_validation->set_rules('father_name', "Father's Name", 'trim|max_length[160]');
            $this->form_validation->set_rules('mother_name', "Mother's Name", 'trim|max_length[160]');
            $this->form_validation->set_rules('guardian_name', "Guardian's Name", 'trim|max_length[160]');
            $this->form_validation->set_rules('guardian_relationship', 'Relationship', 'trim|max_length[60]');
            $this->form_validation->set_rules('contact_number', 'Contact Number', 'trim|max_length[30]');
            $this->form_validation->set_rules('remarks', 'Remarks', 'trim');
            // enrollment fields
            $this->form_validation->set_rules('school_id', 'School', 'required|integer|callback__school_exists');
            $this->form_validation->set_rules('school_year_id', 'School Year', 'required|integer');
            $this->form_validation->set_rules('grade_level', 'Grade Level', 'trim|max_length[25]');
            $this->form_validation->set_rules('section', 'Section', 'trim|max_length[60]');
            $this->form_validation->set_rules('enrollment_status', 'Enrollment Status', 'required|in_list[Enrolled,Transferred In,Transferred Out,Dropped Out,Completed,No Longer Enrolled]');

            if ($this->form_validation->run())
            {
                // duplicate-name guard (non-LRN records)
                $lrn = trim((string) $this->input->post('lrn', TRUE));
                if ( ! $learner && ! $lrn)
                {
                    $dupes = $this->Learner_model->find_possible_duplicates(
                        trim($this->input->post('first_name', TRUE)),
                        trim($this->input->post('last_name', TRUE)),
                        $this->input->post('date_of_birth') ?: NULL
                    );
                    if ($dupes && ! $this->input->post('confirm_duplicate'))
                    {
                        $this->session->set_flashdata('warning',
                            'Possible duplicate found ('.count($dupes).' record(s) with the same name'.($this->input->post('date_of_birth') ? ' and birthdate' : '').'). '
                            .'Re-submit and tick "Confirm add anyway" if this is a different learner.');
                        $this->session->set_userdata('dupes', count($dupes));
                        redirect(current_url().'?dupe=1');
                    }
                }

                $data = array(
                    'lrn'                  => $lrn !== '' ? $lrn : NULL,
                    'last_name'            => trim($this->input->post('last_name', TRUE)),
                    'first_name'           => trim($this->input->post('first_name', TRUE)),
                    'middle_name'          => trim((string) $this->input->post('middle_name', TRUE)) ?: NULL,
                    'extension_name'       => trim((string) $this->input->post('extension_name', TRUE)) ?: NULL,
                    'sex'                  => $this->input->post('sex'),
                    'date_of_birth'        => $this->input->post('date_of_birth') ?: NULL,
                    'place_of_birth'       => trim((string) $this->input->post('place_of_birth', TRUE)) ?: NULL,
                    'address'              => trim((string) $this->input->post('address', TRUE)) ?: NULL,
                    'barangay'             => trim((string) $this->input->post('barangay', TRUE)) ?: NULL,
                    'municipality'         => trim((string) $this->input->post('municipality', TRUE)) ?: NULL,
                    'province'             => trim((string) $this->input->post('province', TRUE)) ?: NULL,
                    'father_name'          => trim((string) $this->input->post('father_name', TRUE)) ?: NULL,
                    'mother_name'          => trim((string) $this->input->post('mother_name', TRUE)) ?: NULL,
                    'guardian_name'        => trim((string) $this->input->post('guardian_name', TRUE)) ?: NULL,
                    'guardian_relationship'=> trim((string) $this->input->post('guardian_relationship', TRUE)) ?: NULL,
                    'contact_number'       => trim((string) $this->input->post('contact_number', TRUE)) ?: NULL,
                    'remarks'              => trim((string) $this->input->post('remarks', TRUE)) ?: NULL,
                );

                $school = $this->School_model->get((int) $this->input->post('school_id'));
                $e_data = array(
                    'school_id'         => $school->id,
                    'division_id'       => $school->division_id,
                    'region_id'         => $school->region_id,
                    'school_year_id'    => (int) $this->input->post('school_year_id'),
                    'grade_level'       => trim((string) $this->input->post('grade_level', TRUE)) ?: NULL,
                    'section'           => trim((string) $this->input->post('section', TRUE)) ?: NULL,
                    'curriculum'        => trim((string) $this->input->post('curriculum', TRUE)) ?: NULL,
                    'enrollment_status' => $this->input->post('enrollment_status'),
                    'is_current'        => 1,
                );

                if ($learner)
                {
                    $e_id = $enrollment ? $enrollment->id : NULL;
                    if ( ! $e_id)
                    {
                        // No active enrollment yet -> insert one via create path data
                        $e_data['learner_id'] = $learner->id;
                        $this->db->insert('learner_school_records', $e_data + array('created_by' => $this->user['id']));
                        $ok = $this->Learner_model->update_with_enrollment($learner->id, $data);
                    }
                    else
                    {
                        $ok = $this->Learner_model->update_with_enrollment($learner->id, $data, $e_id, $e_data);
                    }
                    audit_log('update', 'learners', 'learners', $learner->id, 'Updated learner '.$data['last_name'].', '.$data['first_name'], NULL, $data);
                    $this->session->set_flashdata('success', 'Learner record updated.');
                }
                else
                {
                    $data['status'] = 'active';
                    $new_id = $this->Learner_model->create_with_enrollment($data, $e_data);
                    if ( ! $new_id)
                    {
                        $this->session->set_flashdata('error', 'Failed to save learner record (a record for this school year may already exist).');
                        redirect('superadmin/learners/create');
                    }
                    audit_log('create', 'learners', 'learners', $new_id, 'Created learner '.$data['last_name'].', '.$data['first_name'], NULL, $data);
                    $this->session->set_flashdata('success', 'Learner registered successfully.');
                }
                redirect('superadmin/learners');
            }
        }

        // selections for dependent dropdowns
        $sel_region   = $this->input->post('region_id')   ?: ($enrollment ? $enrollment->region_id   : NULL);
        $sel_division = $this->input->post('division_id') ?: ($enrollment ? $enrollment->division_id : NULL);
        $sel_school   = $this->input->post('school_id')   ?: ($enrollment ? $enrollment->school_id   : NULL);

        $this->data['breadcrumbs'] = array(
            'Super Admin'      => site_url('superadmin/dashboard'),
            'Learner Registry' => site_url('superadmin/learners'),
            $learner ? 'Edit Learner' : 'Register Learner' => NULL,
        );
        $this->render('superadmin/learners/form', array(
            'page_title'   => ($learner ? 'Edit' : 'Register').' Learner - UIEIMS',
            'learner'      => $learner,
            'enrollment'   => $enrollment,
            'regions'      => $this->Region_model->active_options(),
            'divisions'    => $sel_region   ? $this->Division_model->options_by_region($sel_region, FALSE)     : array(),
            'schools'      => $sel_division ? $this->School_model->options_by_division($sel_division, FALSE)   : array(),
            'sel_region'   => $sel_region,
            'sel_division' => $sel_division,
            'sel_school'   => $sel_school,
            'school_years' => $this->School_year_model->options(),
            'grade_levels' => array('Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'),
        ));
    }

    public function _unique_lrn($lrn, $id)
    {
        if ($this->Learner_model->lrn_exists(trim($lrn), $id ? (int) $id : NULL))
        {
            $this->form_validation->set_message('_unique_lrn', 'A learner with this LRN already exists.');
            return FALSE;
        }
        return TRUE;
    }

    public function _valid_date($date)
    {
        if ($date === '' || $date === NULL) return TRUE;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ( ! $d || $d->format('Y-m-d') !== $date)
        {
            $this->form_validation->set_message('_valid_date', 'Enter a valid date (YYYY-MM-DD).');
            return FALSE;
        }
        return TRUE;
    }

    public function _school_exists($school_id)
    {
        if ( ! $this->School_model->get((int) $school_id))
        {
            $this->form_validation->set_message('_school_exists', 'Please select a valid school.');
            return FALSE;
        }
        return TRUE;
    }
}
