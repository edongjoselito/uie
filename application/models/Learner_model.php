<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Learner_model extends MY_Model
{
    protected $table = 'learners';
    protected $default_order = 'last_name ASC, first_name ASC';

    public function insert(array $data)
    {
        return parent::insert(uppercase_learner_names($data));
    }

    public function update($id, array $data)
    {
        return parent::update($id, uppercase_learner_names($data));
    }


    // ---------------------------------------------------------------
    // Identity / duplicate detection
    // ---------------------------------------------------------------

    public function find_by_lrn($lrn)
    {
        return $this->db->where('lrn', $lrn)->limit(1)->get($this->table)->row();
    }

    /**
     * Possible duplicates by name + birthdate (for non-LRN matching).
     */
    public function find_possible_duplicates($first, $last, $dob = NULL)
    {
        $this->db->where('first_name', $first)->where('last_name', $last);
        if ($dob) $this->db->where('date_of_birth', $dob);
        return $this->db->get($this->table)->result();
    }

    public function lrn_exists($lrn, $exclude_id = NULL)
    {
        if (empty($lrn)) return FALSE;
        return $this->exists(array('lrn' => $lrn), $exclude_id);
    }

    // ---------------------------------------------------------------
    // Listing with enrollment context + organizational scoping
    // ---------------------------------------------------------------

    /**
     * Paginated learners joined to their current school record.
     * Applies region/division/school scope filters transparently.
     */
    public function paginate_scoped($limit, $offset, array $f = array())
    {
        $this->_list_query($f);
        return $this->db->order_by('l.last_name, l.first_name')
            ->limit($limit, $offset)->get()->result();
    }

    public function count_scoped(array $f = array())
    {
        $this->_list_query($f);
        return (int) $this->db->count_all_results();
    }

    protected function _list_query(array $f)
    {
        $this->db->select('l.*, lsr.grade_level, lsr.section, lsr.enrollment_status,
                lsr.id AS enrollment_id, s.name AS school_name, s.school_id AS deped_school_id,
                d.name AS division_name, r.name AS region_name, sy.year_label')
            ->from('learners l')
            ->join('learner_school_records lsr', 'lsr.learner_id = l.id AND lsr.is_current = 1 AND lsr.status = "active"', 'left')
            ->join('schools s', 's.id = lsr.school_id', 'left')
            ->join('divisions d', 'd.id = lsr.division_id', 'left')
            ->join('regions r', 'r.id = lsr.region_id', 'left')
            ->join('school_years sy', 'sy.id = lsr.school_year_id', 'left');

        if ( ! empty($f['scope']['region_id']))   $this->db->where('lsr.region_id',   $f['scope']['region_id']);
        if ( ! empty($f['scope']['division_id'])) $this->db->where('lsr.division_id', $f['scope']['division_id']);
        if ( ! empty($f['scope']['school_id']))   $this->db->where('lsr.school_id',   $f['scope']['school_id']);

        if ( ! empty($f['status']))         $this->db->where('l.status', $f['status']);
        if ( ! empty($f['sex']))            $this->db->where('l.sex', $f['sex']);
        if ( ! empty($f['school_year_id'])) $this->db->where('lsr.school_year_id', $f['school_year_id']);
        if ( ! empty($f['region_id']))      $this->db->where('lsr.region_id', $f['region_id']);
        if ( ! empty($f['division_id']))    $this->db->where('lsr.division_id', $f['division_id']);
        if ( ! empty($f['school_id']))      $this->db->where('lsr.school_id', $f['school_id']);
        if ( ! empty($f['grade_level']))    $this->db->where('lsr.grade_level', $f['grade_level']);
        if ( ! empty($f['q']))
        {
            $this->db->group_start()
                ->like('l.lrn', $f['q'])
                ->or_like('l.first_name', $f['q'])
                ->or_like('l.last_name', $f['q'])
                ->or_like('s.name', $f['q'])
                ->group_end();
        }
    }

    // ---------------------------------------------------------------
    // Detail
    // ---------------------------------------------------------------

    public function detail($id)
    {
        $learner = $this->get($id);
        if ( ! $learner) return NULL;

        $learner->enrollments = $this->db->select('lsr.*, s.name AS school_name, s.school_id AS deped_school_id,
                d.name AS division_name, r.name AS region_name, sy.year_label')
            ->from('learner_school_records lsr')
            ->join('schools s', 's.id = lsr.school_id', 'left')
            ->join('divisions d', 'd.id = lsr.division_id', 'left')
            ->join('regions r', 'r.id = lsr.region_id', 'left')
            ->join('school_years sy', 'sy.id = lsr.school_year_id', 'left')
            ->where('lsr.learner_id', $id)
            ->order_by('sy.start_year', 'DESC')
            ->get()->result();

        $learner->history = $this->db->where('learner_id', $id)
            ->order_by('id', 'DESC')->limit(50)
            ->get('learner_record_history')->result();

        return $learner;
    }

    public function get_enrollment($learner_id, $school_year_id = NULL)
    {
        $this->db->where('learner_id', $learner_id)->where('status', 'active');
        if ($school_year_id)
        {
            $this->db->where('school_year_id', $school_year_id);
        }
        else
        {
            $this->db->where('is_current', 1);
        }
        return $this->db->order_by('id', 'DESC')->limit(1)->get('learner_school_records')->row();
    }

    // ---------------------------------------------------------------
    // Persistence
    // ---------------------------------------------------------------

    /**
     * Create learner + first enrollment record. Transactional.
     * @return int learner_id
     */
    public function create_with_enrollment(array $learner, array $enrollment)
    {
        $learner = uppercase_learner_names($learner);
        $this->db->trans_start();

        $learner_id = $this->insert($learner);

        $enrollment['learner_id'] = $learner_id;
        $enrollment['created_by'] = $this->_uid();
        $enrollment['updated_by'] = $this->_uid();
        $this->db->insert('learner_school_records', $enrollment);
        $enrollment_id = $this->db->insert_id();

        $this->_history($learner_id, 'learners', $learner_id, 'create', NULL, $learner);
        $this->_history($learner_id, 'learner_school_records', $enrollment_id, 'create', NULL, $enrollment);

        $this->db->trans_complete();
        return $this->db->trans_status() ? $learner_id : FALSE;
    }

    /**
     * Update learner + optionally the enrollment row. Transactional.
     */
    public function update_with_enrollment($learner_id, array $learner, $enrollment_id = NULL, array $enrollment = array())
    {
        $learner = uppercase_learner_names($learner);
        $old = (array) $this->get($learner_id);

        $this->db->trans_start();
        $this->update($learner_id, $learner);
        $this->_history($learner_id, 'learners', $learner_id, 'update', $old, $learner);

        if ($enrollment_id && $enrollment)
        {
            $old_e = (array) $this->db->where('id', $enrollment_id)->get('learner_school_records')->row();
            $enrollment['updated_by'] = $this->_uid();
            $this->db->where('id', $enrollment_id)->update('learner_school_records', $enrollment);
            $this->_history($learner_id, 'learner_school_records', $enrollment_id, 'update', $old_e, $enrollment);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Archive (soft-delete) a learner. Ordinary CRUD never hard-deletes.
     */
    public function archive($learner_id)
    {
        $this->update($learner_id, array('status' => 'archived'));
        $this->_history($learner_id, 'learners', $learner_id, 'archive', array('status' => 'active'), array('status' => 'archived'));
    }

    public function restore($learner_id)
    {
        $this->update($learner_id, array('status' => 'active'));
        $this->_history($learner_id, 'learners', $learner_id, 'restore', array('status' => 'archived'), array('status' => 'active'));
    }

    protected function _history($learner_id, $table, $record_id, $type, $old, $new)
    {
        $this->db->insert('learner_record_history', array(
            'learner_id'  => $learner_id,
            'table_name'  => $table,
            'record_id'   => $record_id,
            'change_type' => $type,
            'old_values'  => $old !== NULL ? json_encode($old) : NULL,
            'new_values'  => $new !== NULL ? json_encode($new) : NULL,
            'changed_by'  => $this->_uid(),
        ));
    }

    // ---------------------------------------------------------------
    // Scope check helper
    // ---------------------------------------------------------------

    /**
     * Returns the region/division/school ids of a learner's current
     * enrollment (for access-control scoping).
     */
    public function current_scope($learner_id)
    {
        return $this->db->select('region_id, division_id, school_id')
            ->where('learner_id', $learner_id)->where('is_current', 1)
            ->where('status', 'active')->limit(1)
            ->get('learner_school_records')->row_array();
    }
}
