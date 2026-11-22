<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Admin_Controller
{
    public function index()
    {
        extract($this->report_context());
        $selected_group = (string) ($this->input->get('group') ?: 'all');
        if ($selected_group !== 'all' && ! isset($groups[$selected_group])) show_404();
        $summary = $this->School_learner_model->report_summary_scoped($scope, $year_id, $grade, $district);
        $per_page = 25;
        $sections = array();
        foreach ($groups as $key => $label)
        {
            if ($selected_group !== 'all' && $selected_group !== $key) continue;
            $total = $summary[$key]['total'];
            $offset = $selected_group === 'all' ? 0 : max(0, (int) $this->input->get('page'));
            $offset = $total ? min((int) (floor($offset / $per_page) * $per_page), (int) (floor(($total - 1) / $per_page) * $per_page)) : 0;
            $sections[$key] = array('label' => $label, 'total' => $total, 'offset' => $offset,
                'rows' => $this->School_learner_model->report_listing_scoped($scope, $year_id, $grade, $key, $district, $per_page, $offset),
                'pagination' => $selected_group === 'all' ? '' : ci_pagination(site_url('reports'), $total, $per_page, 0));
        }
        $this->output->set_header('Cache-Control: no-store, private');
        $this->data['breadcrumbs'] = array('Dashboard' => site_url('dashboard'), 'Reports' => NULL);
        $this->render('school/reports/index', array('page_title' => 'Learner Group Reports - UIEIMS',
            'organization_name' => $organization_name, 'organization_label' => $organization_label,
            'scope' => $scope, 'districts' => $districts, 'district' => $district,
            'years' => $years, 'year' => $year, 'year_id' => $year_id, 'grade' => $grade,
            'grades' => School_learner_model::grades(), 'groups' => $groups, 'selected_group' => $selected_group,
            'summary' => $summary, 'sections' => $sections));
    }

    public function export($section = '', $format = '')
    {
        extract($this->report_context());
        $this->access_control->require_permission('reports.export');
        if (($section !== 'summary' && ! isset($groups[$section])) || ! in_array($format, array('xlsx', 'docx', 'pdf'), TRUE)) show_404();
        $title = $section === 'summary' ? 'Learner Group Summary' : $groups[$section].' Learner Report';
        if ($section === 'summary')
        {
            $columns = array('Learner Group', 'Male', 'Female', 'Total');
            $summary = $this->School_learner_model->report_summary_scoped($scope, $year_id, $grade, $district);
            $rows = array();
            foreach ($groups as $code => $label) $rows[] = array($label, $summary[$code]['male'], $summary[$code]['female'], $summary[$code]['total']);
            $rows[] = array('Unique Learners', $summary['all']['male'], $summary['all']['female'], $summary['all']['total']);
            $note = 'Groups overlap: learners with multiple tags appear in each applicable program and in Multiple Programs. Unique Learners counts each learner once.';
        }
        else
        {
            $columns = array('#', 'Learner Name', 'LRN', 'Sex', 'Date of Birth', 'Grade Level', 'Section', 'Program Tags', 'Enrollment Status');
            if (empty($scope['school_id'])) array_splice($columns, 2, 0, array('School'));
            $rows = $this->export_rows($scope, $year_id, $grade, $section, $district);
            $note = 'Includes all matching learners in this section, across all report pages. Program memberships may overlap with other groups.';
        }
        $this->load->library('Report_export');
        $grade_label = ($grade ?: 'All Grade Levels').($district ? ' | District: '.$district : '');
        $bytes = $this->report_export->create($format, array('title' => $title, 'school' => $organization_name,
            'year' => $year ? $year->year_label : 'Not Selected', 'grade' => $grade_label,
            'generated' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y, g:i A').' PHT',
            'note' => $note), $columns, $rows);
        audit_log('export', 'reports', NULL, NULL, 'Exported '.$section.' report as '.$format.'; school year '.(int) $year_id.'; scope '.$organization_label.'; district '.($district ?: 'all'));
        $types = array('xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'pdf' => 'application/pdf');
        $filename = 'learner-report-'.$section.'-'.($year ? $year->year_label : 'no-year').'.'.$format;
        $this->output->enable_profiler(FALSE);
        $this->output->set_header('Cache-Control: no-store, private')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: attachment; filename="'.$filename.'"')
            ->set_content_type($types[$format])->set_output($bytes);
    }

    private function export_rows(array $scope, $year_id, $grade, $group, $district)
    {
        $number = 0;
        for ($offset = 0; ; $offset += 500)
        {
            $batch = $this->School_learner_model->report_listing_scoped($scope, $year_id, $grade, $group, $district, 500, $offset);
            foreach ($batch as $row)
            {
                $tags = array();
                foreach (explode(',', (string) $row->program_codes) as $code)
                    if (programs($code)) $tags[] = $code === 'spfl' ? 'FL / SPFL' : programs($code)['short'];
                $values = array(++$number, $row->last_name.', '.$row->first_name.($row->middle_name ? ' '.$row->middle_name : '').($row->extension_name ? ' '.$row->extension_name : ''),
                    (string) $row->lrn, $row->sex, $row->date_of_birth ?: '', $row->grade_level ?: '', $row->section ?: '',
                    implode(', ', $tags) ?: 'No Program Tags', $row->enrollment_status);
                if (empty($scope['school_id'])) array_splice($values, 2, 0, array($row->school_name));
                yield $values;
            }
            if (count($batch) < 500) break;
        }
    }

    private function report_context()
    {
        $this->access_control->require_permission('reports.view');
        $scope = array();
        $school = NULL;
        $organization_name = '';
        $organization_label = '';
        if ($this->user['level'] === 'school' && ! empty($this->user['school_id']))
        {
            $school = $this->db->where('id', $this->user['school_id'])->where('status', 'active')->get('schools')->row();
            if ( ! $school) $this->access_control->deny('An active school assignment is required.');
            $scope['school_id'] = (int) $school->id;
            $organization_name = $school->name;
            $organization_label = 'school';
        }
        elseif ($this->user['level'] === 'division' && ! empty($this->user['division_id']))
        {
            $division = $this->db->where('id', $this->user['division_id'])->where('status', 'active')->get('divisions')->row();
            if ( ! $division) $this->access_control->deny('An active division assignment is required.');
            $scope['division_id'] = (int) $division->id;
            $organization_name = $division->name;
            $organization_label = 'division';
        }
        elseif ($this->user['level'] === 'region' && ! empty($this->user['region_id']))
        {
            $region = $this->db->where('id', $this->user['region_id'])->where('status', 'active')->get('regions')->row();
            if ( ! $region) $this->access_control->deny('An active regional assignment is required.');
            $scope['region_id'] = (int) $region->id;
            $organization_name = $region->name;
            $organization_label = 'region';
        }
        else $this->access_control->deny('An active school, division, or regional assignment is required.');

        $this->load->model(array('School_learner_model', 'School_year_model'));
        $years = $this->School_year_model->options();
        $active = active_sy();
        $year_id = (int) ($this->input->get('school_year_id') ?: ($active ? $active->id : (isset($years[0]) ? $years[0]->id : 0)));
        $year = $this->School_year_model->get($year_id);
        if ( ! $year && $years) show_404();
        $grade = (string) $this->input->get('grade_level');
        if ($grade !== '' && ! in_array($grade, School_learner_model::grades(), TRUE)) show_404();
        $districts = $school ? array() : $this->School_learner_model->report_districts_scoped($scope);
        $district = trim((string) $this->input->get('district'));
        if ($district !== '' && ! in_array($district, $districts, TRUE)) show_404();

        $groups = array('sned' => 'SNED', 'madrasah' => 'Madrasah', 'iped' => 'IPEd', 'spfl' => 'FL / SPFL', 'multiple' => 'Multiple Programs', 'untagged' => 'No Program Tags');
        return compact('scope', 'school', 'organization_name', 'organization_label', 'districts', 'district', 'years', 'year_id', 'year', 'grade', 'groups');
    }
}
