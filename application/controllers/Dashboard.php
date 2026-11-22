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

        $this->load->model('Learner_model');
        $this->load->model('School_model');
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
            'total_schools'  => $this->School_model->count_filtered(array_filter(array(
                'region_id'   => $scope['region_id'],
                'division_id' => $scope['division_id'],
                'status'      => 'active',
                'school_id'   => $scope['school_id'] ? $scope['school_id'] : NULL,
            ))),
        );

        // Org labels
        if ($scope['region_id'])   $data['region_label']   = $this->db->select('name')->where('id', $scope['region_id'])->get('regions')->row('name');
        if ($scope['division_id']) $data['division_label'] = $this->db->select('name')->where('id', $scope['division_id'])->get('divisions')->row('name');
        if ($scope['school_id'])   $data['school_label']   = $this->db->select('name')->where('id', $scope['school_id'])->get('schools')->row('name');

        $this->data['breadcrumbs'] = array('Dashboard' => NULL);
        $this->render('dashboard/index', $data);
    }
}
