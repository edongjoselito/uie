<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Superadmin_Controller
{
    public function index()
    {
        $sy = active_sy();
        $sy_id = $sy ? $sy->id : 0;

        // Program participation counts (school-year aware, live queries)
        $counts = array(
            'regions'    => $this->db->where('status', 'active')->count_all_results('regions'),
            'divisions'  => $this->db->where('status', 'active')->count_all_results('divisions'),
            'schools'    => $this->db->where('status', 'active')->count_all_results('schools'),
            'users'      => $this->db->where('status', 'active')->count_all_results('users'),
            'iped'       => $this->db->where('status', 'active')->count_all_results('iped_learner_profiles'),
            'madrasah'   => $this->db->where('status', 'active')->count_all_results('madrasah_learner_profiles'),
            'sned'       => $this->db->where('status', 'active')->count_all_results('sned_learner_profiles'),
            'submitted'  => $this->db->where_in('status', array('submitted','under_validation','resubmitted'))->count_all_results('data_submissions'),
            'validated'  => $this->db->where('status', 'validated')->count_all_results('data_submissions'),
        );

        // Unique learners (current school year, active enrollments)
        $counts['unique_learners'] = (int) $this->db->query(
            'SELECT COUNT(DISTINCT learner_id) AS c FROM uie_learner_school_records WHERE status = ? AND school_year_id = ?',
            array('active', $sy_id)
        )->row('c');
        $counts['total_learners'] = $this->db->where('status', 'active')->count_all_results('learners');

        // Learners per region (active SY) for chart
        $by_region = $this->db->query(
            'SELECT r.name, COUNT(DISTINCT lsr.learner_id) AS total
             FROM uie_regions r
             LEFT JOIN uie_learner_school_records lsr ON lsr.region_id = r.id AND lsr.status = "active" AND lsr.school_year_id = ?
             WHERE r.status = "active"
             GROUP BY r.id ORDER BY total DESC LIMIT 12', array($sy_id))->result();

        // Enrollment trend by school year
        $trend = $this->db->query(
            'SELECT sy.year_label, COUNT(DISTINCT lsr.learner_id) AS total
             FROM uie_school_years sy
             LEFT JOIN uie_learner_school_records lsr ON lsr.school_year_id = sy.id AND lsr.status = "active"
             GROUP BY sy.id ORDER BY sy.start_year')->result();

        $data = array(
            'page_title'   => 'Super Admin Dashboard - UIEIMS',
            'counts'       => $counts,
            'sy'           => $sy,
            'by_region'    => $by_region,
            'trend'        => $trend,
            'modules'      => $this->db->get('system_modules')->result(),
            'recent_audit' => $this->db->order_by('id', 'DESC')->limit(8)->get('audit_logs')->result(),
        );

        $this->data['breadcrumbs'] = array('Super Admin' => NULL, 'Dashboard' => NULL);
        $this->render('superadmin/dashboard', $data);
    }
}
