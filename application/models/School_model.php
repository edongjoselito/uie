<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class School_model extends MY_Model
{
    protected $table = 'schools';
    protected $default_order = 'name ASC';

    public function options_by_division($division_id, $active_only = TRUE)
    {
        $this->db->where('division_id', $division_id);
        if ($active_only) $this->db->where('status', 'active');
        return $this->db->order_by('name')->get($this->table)->result();
    }

    public function paginate_with_org($limit, $offset, array $f = array())
    {
        $this->_join_org();
        $this->_filters($f);
        return $this->db->select('s.*, d.name AS division_name, r.name AS region_name')
            ->order_by('s.name')
            ->limit($limit, $offset)
            ->get()->result();
    }

    public function count_filtered(array $f = array())
    {
        $this->_join_org();
        $this->_filters($f);
        return (int) $this->db->count_all_results();
    }

    protected function _join_org()
    {
        $this->db->from($this->table.' s')
            ->join('divisions d', 'd.id = s.division_id', 'left')
            ->join('regions r', 'r.id = s.region_id', 'left');
    }

    protected function _filters(array $f)
    {
        if ( ! empty($f['region_id']))   $this->db->where('s.region_id', $f['region_id']);
        if ( ! empty($f['division_id'])) $this->db->where('s.division_id', $f['division_id']);
        if ( ! empty($f['school_id']))   $this->db->where('s.id', $f['school_id']);
        if ( ! empty($f['status']))      $this->db->where('s.status', $f['status']);
        if ( ! empty($f['school_type'])) $this->db->where('s.school_type', $f['school_type']);
        if ( ! empty($f['q']))
        {
            $this->db->group_start()
                ->like('s.name', $f['q'])
                ->or_like('s.school_id', $f['q'])
                ->or_like('s.municipality', $f['q'])
                ->group_end();
        }
    }

    public function detail($id)
    {
        return $this->db->select('s.*, d.name AS division_name, r.name AS region_name,
                (SELECT COUNT(DISTINCT lsr.learner_id) FROM uie_learner_school_records lsr WHERE lsr.school_id = s.id AND lsr.status = "active") AS learner_count')
            ->from($this->table.' s')
            ->join('divisions d', 'd.id = s.division_id', 'left')
            ->join('regions r', 'r.id = s.region_id', 'left')
            ->where('s.id', $id)
            ->limit(1)->get()->row();
    }

    public function school_id_exists($deped_school_id, $exclude_id = NULL)
    {
        return $this->exists(array('school_id' => $deped_school_id), $exclude_id);
    }
}
