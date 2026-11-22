<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Entry dashboard - routes the authenticated user to the dashboard
 * matching their organizational level. Non-system levels currently
 * share a scoped summary dashboard (expanded in later phases).
 */
class Dashboard extends Admin_Controller
{
    public function index()
    {
        if ($this->user['level'] === 'system')
        {
            redirect('superadmin/dashboard');
        }

        if ($this->user['level'] === 'school')
        {
            $school = empty($this->user['school_id']) ? NULL : $this->db->where('id', $this->user['school_id'])
                ->where('status', 'active')->get('schools')->row();
            if ( ! $school) $this->access_control->deny('An active school assignment is required.');
        }

        $this->load->model('Learner_model');
        $this->load->model('Division_model');

        $scope = array(
            'region_id'   => $this->access_control->scope_region_id(),
            'division_id' => $this->access_control->scope_division_id(),
            'school_id'   => $this->access_control->scope_school_id(),
        );
        // Region users scope by region only; division by division; school by school
        $filters = array('scope' => array(), 'status' => 'active');
        if ($scope['region_id'])   $filters['scope']['region_id']   = $scope['region_id'];
        if ($scope['division_id']) $filters['scope']['division_id'] = $scope['division_id'];
        if ($scope['school_id'])   $filters['scope']['school_id']   = $scope['school_id'];

        $data = array(
            'page_title'  => 'Dashboard - UIEIMS',
            'scope'       => $scope,
            'sy'          => active_sy(),
            'total_learners' => $this->Learner_model->count_scoped($filters),
        );

        $sy = $data['sy'];
        $counts = array_fill_keys(array_keys(programs()), 0);
        $this->db->select('t.program, COUNT(DISTINCT e.learner_id) AS total', FALSE)
            ->from('learner_program_tags t')->join('learner_school_records e', 'e.id = t.learner_school_record_id')
            ->join('learners l', 'l.id = e.learner_id')->where('e.status', 'active')->where('l.status', 'active')
            ->where('e.school_year_id', $sy ? $sy->id : 0);
        foreach (array('region_id', 'division_id', 'school_id') as $key)
            if ($scope[$key]) $this->db->where('e.'.$key, $scope[$key]);
        foreach ($this->db->group_by('t.program')->get()->result() as $row) $counts[$row->program] = (int) $row->total;
        $data['program_counts'] = $counts;
        if ($this->user['level'] === 'school')
        {
            $this->load->model('School_learner_model');
            $data['total_learners'] = $this->School_learner_model->count_rows((int) $scope['school_id'], $sy ? $sy->id : 0);
        }

        // Org labels
        if ($scope['region_id'])   $data['region_label']   = $this->db->select('name')->where('id', $scope['region_id'])->get('regions')->row('name');
        if ($scope['division_id']) $data['division_label'] = $this->db->select('name')->where('id', $scope['division_id'])->get('divisions')->row('name');
        if ($scope['school_id'])   $data['school_label']   = $this->db->select('name')->where('id', $scope['school_id'])->get('schools')->row('name');

        $this->data['breadcrumbs'] = array('Dashboard' => NULL);
        $this->render('dashboard/index', $data);
    }
}
