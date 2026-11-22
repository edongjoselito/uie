<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Learners extends Admin_Controller
{
    private $school;
    private $division;

    public function __construct()
    {
        parent::__construct();
        $this->access_control->require_permission('learners.view');
        if ($this->user['level'] === 'school' && ! empty($this->user['school_id']))
        {
            $this->school = $this->db->where('id', $this->user['school_id'])->where('status', 'active')->get('schools')->row();
            if ( ! $this->school) $this->access_control->deny('An active school assignment is required.');
            $this->load->model(array('School_learner_model', 'School_year_model'));
        }
        elseif ($this->user['level'] === 'division' && ! empty($this->user['division_id']))
        {
            $this->division = $this->db->where('id', $this->user['division_id'])->where('status', 'active')->get('divisions')->row();
            if ( ! $this->division) $this->access_control->deny('An active division assignment is required.');
            $this->load->model('Learner_model');
        }
        else $this->access_control->deny('An active school or division assignment is required.');
    }

    public function index()
    {
        if ($this->user['level'] === 'division')
        {
            $this->division_index();
            return;
        }
        $years = $this->School_year_model->options();
        $active = active_sy();
        $year_id = (int) ($this->input->get('school_year_id') ?: ($active ? $active->id : (isset($years[0]) ? $years[0]->id : 0)));
        $year = $this->School_year_model->get($year_id);
        $q = substr(trim((string) $this->input->get('q')), 0, 120);
        $grade = (string) $this->input->get('grade_level');
        $program = (string) $this->input->get('program');
        if ($program !== '' && ! programs($program)) show_404();
        if ($grade !== '' && ! in_array($grade, School_learner_model::grades(), TRUE)) show_404();
        $per_page = (int) $this->input->get('per_page');
        if ( ! in_array($per_page, array(10, 25, 50, 100), TRUE)) $per_page = 25;
        $sort = (string) $this->input->get('sort');
        if ( ! in_array($sort, array('name', 'lrn', 'grade'), TRUE)) $sort = 'name';
        $direction = $this->input->get('direction') === 'desc' ? 'desc' : 'asc';
        $offset = max(0, (int) $this->input->get('page'));
        $total = $this->School_learner_model->count_rows($this->school->id, $year_id, $q, $grade, $program);
        $offset = $total ? min((int) (floor($offset / $per_page) * $per_page), (int) (floor(($total - 1) / $per_page) * $per_page)) : 0;
        $this->data['breadcrumbs'] = array('Dashboard' => site_url('dashboard'), 'Learners' => NULL, 'Profile' => NULL);
        $this->render('school/learners/index', array('page_title' => 'Learner Profiles - UIEIMS',
            'school' => $this->school, 'years' => $years, 'year' => $year, 'year_id' => $year_id,
            'q' => $q, 'grade' => $grade, 'program' => $program, 'grades' => School_learner_model::grades(),
            'rows' => $this->School_learner_model->listing($this->school->id, $year_id, $q, $grade, $program, $per_page, $offset, $sort, $direction),
            'overview' => $this->School_learner_model->year_overview($this->school->id, $year_id),
            'total' => $total, 'offset' => $offset, 'per_page' => $per_page, 'sort' => $sort, 'direction' => $direction,
            'pagination' => ci_pagination(site_url('learners'), $total, $per_page, 0)));
    }

    public function create()
    {
        if ($this->user['level'] !== 'school') $this->access_control->deny('Division learner access is read-only.');
        $this->access_control->require_permission('learners.create');
        $this->form(NULL);
    }

    public function edit($id = 0)
    {
        if ($this->user['level'] !== 'school') $this->access_control->deny('Division learner access is read-only.');
        $this->access_control->require_permission('learners.edit');
        $enrollment = $this->School_learner_model->enrollment((int) $id, $this->school->id);
        if ( ! $enrollment) show_404();
        $this->form($enrollment);
    }

    private function division_index()
    {
        $per_page = 25;
        $q = substr(trim((string) $this->input->get('q')), 0, 120);
        $offset = max(0, (int) $this->input->get('page'));
        $filters = array('scope' => array('division_id' => (int) $this->division->id), 'status' => 'active');
        if ($q !== '') $filters['q'] = $q;
        $total = $this->Learner_model->count_scoped($filters);
        $offset = $total ? min((int) (floor($offset / $per_page) * $per_page), (int) (floor(($total - 1) / $per_page) * $per_page)) : 0;
        $this->data['breadcrumbs'] = array('Dashboard' => site_url('dashboard'), 'Learners' => NULL);
        $this->render('division/learners/index', array(
            'page_title' => 'Division Learners - UIEIMS',
            'division' => $this->division,
            'rows' => $this->Learner_model->paginate_scoped($per_page, $offset, $filters),
            'q' => $q,
            'total' => $total,
            'pagination' => ci_pagination(site_url('learners'), $total, $per_page, 0),
        ));
    }

    private function form($enrollment)
    {
        $years = $this->School_year_model->options();
        $active = active_sy();
        $year_id = $enrollment ? $enrollment->school_year_id : (int) ($this->input->post('school_year_id') ?: $this->input->get('school_year_id') ?: ($active ? $active->id : 0));
        $year = $this->School_year_model->get($year_id);
        if ($enrollment && ( ! $year || $year->status !== 'open')) show_error('This school year is closed for encoding.', 403);
        $error = NULL;
        $profile_options = $this->School_learner_model->program_options();
        $profile_data = $enrollment ? $this->School_learner_model->program_profiles($enrollment->id) : array();
        if ($this->input->method(TRUE) === 'POST')
        {
            $this->form_validation->set_rules('school_year_id', 'School year', 'required|integer');
            $this->form_validation->set_rules('grade_level', 'Grade level', 'required|in_list['.implode(',', School_learner_model::grades()).']');
            $this->form_validation->set_rules('section', 'Section', 'trim|max_length[60]');
            $learner_id = $enrollment ? (int) $enrollment->learner_id : (int) $this->input->post('learner_id');
            if ( ! $enrollment && ! $learner_id)
            {
                $this->form_validation->set_rules('lrn', 'LRN', 'trim|regex_match[/^[0-9]{12}$/]');
                $this->form_validation->set_rules('first_name', 'First name', 'required|trim|max_length[80]');
                $this->form_validation->set_rules('last_name', 'Last name', 'required|trim|max_length[80]');
                $this->form_validation->set_rules('middle_name', 'Middle name', 'trim|max_length[80]');
                $this->form_validation->set_rules('extension_name', 'Extension name', 'trim|max_length[20]');
                $this->form_validation->set_rules('sex', 'Sex', 'required|in_list[Male,Female]');
                $this->form_validation->set_rules('date_of_birth', 'Date of birth', 'required|callback__birthdate');
                $this->form_validation->set_rules('place_of_birth', 'Place of birth', 'trim|max_length[200]');
                $this->form_validation->set_rules('mother_tongue', 'Mother tongue', 'trim|max_length[120]');
                $this->form_validation->set_rules('ip_ethnicity', 'IP (ethnic group)', 'trim|max_length[120]');
                $this->form_validation->set_rules('religion', 'Religion', 'trim|max_length[120]');
                $this->form_validation->set_rules('address', 'Address', 'trim|max_length[255]');
                $this->form_validation->set_rules('barangay', 'Barangay', 'trim|max_length[120]');
                $this->form_validation->set_rules('municipality', 'Municipality/City', 'trim|max_length[120]');
                $this->form_validation->set_rules('province', 'Province', 'trim|max_length[120]');
                $this->form_validation->set_rules('father_name', "Father's name", 'trim|max_length[160]');
                $this->form_validation->set_rules('mother_name', "Mother's maiden name", 'trim|max_length[160]');
                $this->form_validation->set_rules('guardian_name', 'Guardian name', 'trim|max_length[160]');
                $this->form_validation->set_rules('guardian_relationship', 'Guardian relationship', 'trim|max_length[60]');
                $this->form_validation->set_rules('contact_number', 'Contact number', 'trim|max_length[30]');
            }
            $tags = $this->input->post('programs');
            if ($tags === NULL) $tags = array();
            $tags_valid = is_array($tags) && count($tags) <= 4;
            if ($tags_valid) foreach ($tags as $tag) if ( ! is_string($tag) || ! programs($tag)) $tags_valid = FALSE;
            if ( ! $tags_valid) $error = 'Select valid program tags.';
            $posted_profiles = $this->input->post('profiles');
            if ( ! is_array($posted_profiles)) $posted_profiles = array();
            $normalized_profiles = $this->normalize_program_profiles($posted_profiles, $tags_valid ? array_unique($tags) : array());
            if ($normalized_profiles === FALSE)
            {
                $error = 'Check the additional program information and try again.';
                $profile_data = $posted_profiles;
            }
            else $profile_data = $normalized_profiles + $profile_data;
            if ( ! $year || $year->status !== 'open') $error = 'Choose a school year that is open for encoding.';
            if ($enrollment && (int) $this->input->post('school_year_id') !== (int) $enrollment->school_year_id) $error = 'The school year of an existing enrollment cannot be changed. Add an enrollment for the other year.';
            if ($this->form_validation->run() && ! $error)
            {
                $identity = array();
                if ( ! $enrollment && ! $learner_id)
                {
                    foreach (array('lrn', 'first_name', 'last_name', 'middle_name', 'extension_name', 'sex', 'date_of_birth',
                        'place_of_birth', 'mother_tongue', 'ip_ethnicity', 'religion', 'address', 'barangay', 'municipality',
                        'province', 'father_name', 'mother_name', 'guardian_name', 'guardian_relationship', 'contact_number') as $field)
                        $identity[$field] = trim((string) $this->input->post($field)) ?: NULL;
                }
                $id = $this->School_learner_model->save($this->school->id, (int) $year_id, $learner_id, $identity,
                    array('grade_level' => $this->input->post('grade_level'), 'section' => trim((string) $this->input->post('section')) ?: NULL),
                    $tags, $this->user['id'], $enrollment ? $enrollment->id : NULL, $normalized_profiles);
                if ($id)
                {
                    audit_log($enrollment ? 'update' : 'create', 'learners', 'learner_school_records', $id, 'School learner enrollment and program tags saved');
                    $this->session->set_flashdata('success', 'Learner enrollment and program tags saved.');
                    redirect('learners?school_year_id='.(int) $year_id);
                }
                $error = 'Unable to save. The learner may already have an enrollment or registered LRN. For an existing learner, select their school record below. Contact your administrator for transfers or duplicate records.';
            }
        }
        $this->data['breadcrumbs'] = array('Dashboard' => site_url('dashboard'), 'School Learners' => site_url('learners'), $enrollment ? 'Edit Enrollment' : 'Encode Learner' => NULL);
        $this->render('school/learners/form', array('page_title' => 'Encode Learner - UIEIMS', 'school' => $this->school,
            'enrollment' => $enrollment, 'years' => $years, 'year_id' => $year_id, 'error' => $error,
            'grades' => School_learner_model::grades(), 'known' => $enrollment ? array() : $this->School_learner_model->known_learners($this->school->id),
            'profile_options' => $profile_options, 'profile_data' => $profile_data,
            'selected_tags' => $this->input->method(TRUE) === 'POST' ? (is_array($this->input->post('programs')) ? $this->input->post('programs') : array()) : ($enrollment ? $this->School_learner_model->tags($enrollment->id) : array())));
    }

    private function normalize_program_profiles(array $input, array $tags)
    {
        $fields = array(
            'iped' => array('icc_id' => 'id', 'ethnic_group_id' => 'id', 'tribe' => 120, 'indigenous_language' => 120, 'mother_tongue' => 120, 'ancestral_area' => 200, 'other_cultural_info' => 4000, 'program_participation' => 150, 'educational_assistance' => 255, 'program_support' => 255, 'community_participation' => 255, 'remarks' => 4000),
            'madrasah' => array('alive_participation' => 'yes_no', 'arabic_proficiency' => 60, 'islamic_values_participation' => 'yes_no', 'years_in_program' => 'years', 'participation_status' => 60, 'educational_assistance' => 255, 'program_support' => 255, 'remarks' => 4000, 'program_ids' => 'ids'),
            'sned' => array('functional_needs' => 4000, 'assistive_devices' => 255, 'accessibility_requirements' => 255, 'iep_status' => 'iep_status', 'accommodations' => 4000, 'assistive_technology' => 255, 'interventions' => 4000, 'therapy_support' => 255, 'support_services' => 255, 'progress_monitoring' => 4000, 'referral_info' => 255, 'doc_reference' => 200, 'last_profile_update' => 'date', 'remarks' => 4000, 'disability_category_ids' => 'ids'),
            'spfl' => array('foreign_language' => 120, 'proficiency_level' => 60, 'educational_assistance' => 255, 'program_support' => 255, 'remarks' => 4000),
        );
        $normalized = array();
        foreach (array_unique($tags) as $program)
        {
            $source = isset($input[$program]) ? $input[$program] : array();
            if ( ! is_array($source)) return FALSE;
            $normalized[$program] = array();
            foreach ($fields[$program] as $field => $rule)
            {
                if ($rule === 'ids')
                {
                    $ids = isset($source[$field]) ? $source[$field] : array();
                    if ( ! is_array($ids)) return FALSE;
                    $normalized[$program][$field] = array();
                    foreach ($ids as $id)
                    {
                        if ( ! ctype_digit((string) $id) || (int) $id < 1) return FALSE;
                        $normalized[$program][$field][] = (int) $id;
                    }
                    $normalized[$program][$field] = array_values(array_unique($normalized[$program][$field]));
                    continue;
                }
                $value = isset($source[$field]) ? trim((string) $source[$field]) : '';
                if ($rule === 'id')
                {
                    if ($value !== '' && ( ! ctype_digit($value) || (int) $value < 1)) return FALSE;
                    $normalized[$program][$field] = $value === '' ? NULL : (int) $value;
                }
                elseif ($rule === 'yes_no')
                {
                    if ($value !== '' && ! in_array($value, array('Yes', 'No'), TRUE)) return FALSE;
                    $normalized[$program][$field] = $value === '' ? NULL : $value;
                }
                elseif ($rule === 'years')
                {
                    if ($value !== '' && ( ! ctype_digit($value) || (int) $value > 255)) return FALSE;
                    $normalized[$program][$field] = $value === '' ? NULL : (int) $value;
                }
                elseif ($rule === 'iep_status')
                {
                    if ( ! in_array($value, array('None', 'In Development', 'Implemented', 'Under Review'), TRUE)) return FALSE;
                    $normalized[$program][$field] = $value;
                }
                elseif ($rule === 'date')
                {
                    $date = $value === '' ? FALSE : DateTime::createFromFormat('!Y-m-d', $value);
                    if ($value !== '' && ( ! $date || $date->format('Y-m-d') !== $value || $value > date('Y-m-d'))) return FALSE;
                    $normalized[$program][$field] = $value === '' ? NULL : $value;
                }
                else
                {
                    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
                    if ($length > $rule) return FALSE;
                    $normalized[$program][$field] = $value === '' ? NULL : $value;
                }
            }
        }
        return $normalized;
    }

    public function _birthdate($value)
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        if ($date && $date->format('Y-m-d') === $value && $value <= date('Y-m-d')) return TRUE;
        $this->form_validation->set_message('_birthdate', 'Enter a valid date of birth that is not in the future.');
        return FALSE;
    }

    // ---------------------------------------------------------------
    // SF 1 (School Register) upload import
    // ---------------------------------------------------------------

    public function import()
    {
        if ($this->user['level'] !== 'school') $this->access_control->deny('Division learner access is read-only.');
        $this->access_control->require_permission('learners.import');
        $this->load->library('Sf1_import');
        $this->import_cache_gc();
        if ($this->input->method(TRUE) === 'POST' && $this->input->post('import_stage') === 'commit')
        {
            $this->import_commit();
            return;
        }
        $years = $this->School_year_model->options();
        $active = active_sy();
        $year_id = (int) ($this->input->post('school_year_id') ?: $this->input->get('school_year_id') ?: ($active ? $active->id : 0));
        $error = NULL;
        $preview = NULL;
        if ($this->input->method(TRUE) === 'POST')
        {
            $year = $this->School_year_model->get($year_id);
            $grade = (string) $this->input->post('grade_level');
            $section = trim((string) $this->input->post('section'));
            $tag_iped = (bool) $this->input->post('tag_iped');
            if ( ! $year || $year->status !== 'open') $error = 'Choose a school year that is open for encoding.';
            elseif ( ! in_array($grade, School_learner_model::grades(), TRUE)) $error = 'Choose the grade level this register applies to.';
            elseif ((function_exists('mb_strlen') ? mb_strlen($section, 'UTF-8') : strlen($section)) > 60) $error = 'Section is too long.';
            else
            {
                $file = $this->import_uploaded_file();
                if (is_string($file)) $error = $file;
                else
                {
                    $parsed = $this->sf1_import->parse($file['tmp_name']);
                    if ( ! $parsed['ok']) $error = $parsed['error'];
                    else
                    {
                        $token = bin2hex(random_bytes(16));
                        $payload = array(
                            'school_id' => (int) $this->school->id,
                            'year_id' => (int) $year_id,
                            'grade' => $grade,
                            'section' => $section,
                            'tag_iped' => $tag_iped,
                            'meta' => $parsed['meta'],
                            'rows' => $parsed['rows'],
                        );
                        foreach ($payload['rows'] as $i => $row)
                        {
                            if ( ! $row['valid']) continue;
                            $status = $this->School_learner_model->preview_status($this->school->id, $year_id, $row['fields']);
                            $payload['rows'][$i]['status'] = $status['status'];
                            $payload['rows'][$i]['status_message'] = $status['message'];
                            if ($status['status'] === 'error') $payload['rows'][$i]['valid'] = FALSE;
                        }
                        $this->session->set_userdata('sf1_import_token', $token);
                        file_put_contents(APPPATH.'cache/sf1_import_'.$token.'.json', json_encode($payload), LOCK_EX);
                        $preview = $payload + array('token' => $token, 'filename' => $file['name'], 'year_label' => $year->year_label);
                    }
                }
            }
        }
        $this->data['breadcrumbs'] = array('Dashboard' => site_url('dashboard'), 'Learner Profiles' => site_url('learners'), 'Import SF 1' => NULL);
        $this->render('school/learners/import', array(
            'page_title' => 'Import SF 1 - UIEIMS', 'school' => $this->school,
            'years' => $years, 'year_id' => $year_id, 'error' => $error, 'preview' => $preview,
            'grades' => School_learner_model::grades(),
            'can_import' => $this->access_control->can('learners.import'),
        ));
    }

    /** Finalize a previewed import for the rows the user kept selected. */
    private function import_commit()
    {
        $token = (string) $this->input->post('import_token');
        $path = APPPATH.'cache/sf1_import_'.$token.'.json';
        if ( ! preg_match('/^[a-f0-9]{32}$/', $token)
            || $token !== $this->session->userdata('sf1_import_token')
            || ! is_file($path))
        {
            show_error('The import preview has expired. Upload the SF 1 file again.', 403);
        }
        $payload = json_decode((string) file_get_contents($path), TRUE);
        if ( ! is_array($payload) || empty($payload['rows']) || (int) $payload['school_id'] !== (int) $this->school->id)
        {
            @unlink($path);
            show_error('The import preview is no longer valid. Upload the SF 1 file again.', 403);
        }
        $year = $this->School_year_model->get((int) $payload['year_id']);
        if ( ! $year || $year->status !== 'open')
        {
            @unlink($path);
            $this->session->unset_userdata('sf1_import_token');
            show_error('This school year is closed for encoding.', 403);
        }
        $selected = $this->input->post('rows');
        $selected = is_array($selected) ? array_map('intval', $selected) : array();
        $counts = array('created' => 0, 'enrolled' => 0, 'updated' => 0, 'error' => 0);
        $messages = array();
        foreach ($payload['rows'] as $i => $row)
        {
            if ( ! in_array((int) $i, $selected, TRUE) || empty($row['valid'])) continue;
            $tags = array();
            $profiles = array();
            if ( ! empty($payload['tag_iped']) && ! empty($row['fields']['ip_ethnicity']))
            {
                $tags = array('iped');
                $group_id = $this->School_learner_model->find_ethnic_group_id($row['fields']['ip_ethnicity']);
                if ($group_id) $profiles = array('iped' => array('ethnic_group_id' => $group_id));
            }
            $result = $this->School_learner_model->import_row($this->school->id, (int) $payload['year_id'],
                $row['fields'], $payload['grade'], $payload['section'], $tags, $this->user['id'], $profiles);
            $counts[$result['result']]++;
            if ($result['result'] === 'error')
            {
                $name = trim($row['fields']['last_name'].', '.$row['fields']['first_name'], ' ,');
                $messages[] = 'Row '.$row['source_row'].' ('.($name !== '' ? $name : 'no name').'): '.$result['message'];
            }
        }
        @unlink($path);
        $this->session->unset_userdata('sf1_import_token');
        $done = $counts['created'] + $counts['enrolled'] + $counts['updated'];
        audit_log('import', 'learners', 'learners', NULL,
            'SF 1 import for '.$this->school->name.': '.$done.' saved ('.$counts['created'].' new, '.$counts['enrolled'].' enrolled, '.$counts['updated'].' updated), '.$counts['error'].' failed');
        if ($done)
        {
            $this->session->set_flashdata('success',
                'SF 1 import finished: '.$counts['created'].' new learner'.($counts['created'] === 1 ? '' : 's').', '
                .$counts['enrolled'].' enrolled for the year, '.$counts['updated'].' existing enrollment'.($counts['updated'] === 1 ? '' : 's').' refreshed.');
        }
        if ($counts['error'])
        {
            $this->session->set_flashdata('warning', $counts['error'].' row'.($counts['error'] === 1 ? '' : 's').' could not be imported. '
                .e(implode(' ', array_slice($messages, 0, 5))));
        }
        if ( ! $done && ! $counts['error']) $this->session->set_flashdata('info', 'No rows were selected for import.');
        redirect('learners?school_year_id='.(int) $payload['year_id']);
    }

    /** Download a blank SF 1 template with the expected column headers. */
    public function sf1_template()
    {
        if ($this->user['level'] !== 'school') $this->access_control->deny('Division learner access is read-only.');
        $this->access_control->require_permission('learners.import');
        require_once FCPATH.'vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SF 1');
        $headers = array('LRN', 'Last Name', 'First Name', 'Middle Name', 'Extension Name', 'Sex',
            'Birth Date (mm/dd/yyyy)', 'Mother Tongue', 'IP (Ethnic Group)', 'Religion',
            'House No./Street/Sitio', 'Barangay', 'Municipality/City', 'Province',
            "Father's Name", "Mother's Maiden Name", "Guardian's Name", 'Relationship', 'Contact Number', 'Remarks');
        foreach ($headers as $i => $label)
        {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.'1', $label);
            $sheet->getColumnDimension($col)->setWidth(max(14, strlen($label) + 2));
        }
        $sheet->getStyle('A1:T1')->getFont()->setBold(TRUE);
        $sheet->getCell('A2')->setValueExplicit('123456789012', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('B2', 'DELA CRUZ');
        $sheet->setCellValue('C2', 'JUAN');
        $sheet->setCellValue('F2', 'M');
        $sheet->setCellValue('G2', '01/15/2016');
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="sf1_import_template.xlsx"');
        header('Cache-Control: private, no-store');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** Validate the uploaded SF1 file. Returns file info array or an error string. */
    private function import_uploaded_file()
    {
        if (empty($_FILES['sf1_file']) || ! is_array($_FILES['sf1_file']) || $_FILES['sf1_file']['error'] === UPLOAD_ERR_NO_FILE)
        {
            return 'Choose the SF 1 spreadsheet to upload.';
        }
        $file = $_FILES['sf1_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) return 'The upload failed (error '.$file['error'].'). Try again with a smaller file.';
        if ($file['size'] > 8 * 1024 * 1024) return 'The file exceeds the 8 MB limit.';
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, array('xlsx', 'xls', 'csv'), TRUE)) return 'Upload an Excel workbook (.xlsx or .xls) or a .csv export.';
        if ( ! is_uploaded_file($file['tmp_name'])) return 'The upload could not be verified.';
        return array('tmp_name' => $file['tmp_name'], 'name' => $file['name']);
    }

    /** Remove stale preview payloads (kept at most one hour). */
    private function import_cache_gc()
    {
        foreach (glob(APPPATH.'cache/sf1_import_*.json') ?: array() as $path)
        {
            if (filemtime($path) < time() - 3600) @unlink($path);
        }
    }
}
