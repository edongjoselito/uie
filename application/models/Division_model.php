<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Division_model extends MY_Model
{
    protected $table = 'divisions';
    protected $default_order = 'name ASC';

    public function options_by_region($region_id, $active_only = TRUE)
    {
        $this->db->where('region_id', $region_id);
        if ($active_only) $this->db->where('status', 'active');
        return $this->db->order_by('name')->get($this->table)->result();
    }

    public function paginate_with_region($limit, $offset, array $f = array())
    {
        $this->_join_region();
        $this->_filters($f);
        return $this->db->select('d.*, r.name AS region_name, r.code AS region_code')
            ->order_by('r.name, d.name')
            ->limit($limit, $offset)
            ->get()->result();
    }

    public function count_filtered(array $f = array())
    {
        $this->_join_region();
        $this->_filters($f);
        return (int) $this->db->count_all_results();
    }

    protected function _join_region()
    {
        $this->db->from($this->table.' d')
            ->join('regions r', 'r.id = d.region_id', 'left');
    }

    protected function _filters(array $f)
    {
        if ( ! empty($f['region_id'])) $this->db->where('d.region_id', $f['region_id']);
        if ( ! empty($f['status']))    $this->db->where('d.status', $f['status']);
        if ( ! empty($f['q']))
        {
            $this->db->group_start()
                ->like('d.name', $f['q'])
                ->or_like('d.code', $f['q'])
                ->group_end();
        }
    }

    public function detail($id)
    {
        return $this->db->select('d.*, r.name AS region_name,
                (SELECT COUNT(*) FROM uie_schools s WHERE s.division_id = d.id) AS school_count,
                (SELECT COUNT(DISTINCT lsr.learner_id) FROM uie_learner_school_records lsr WHERE lsr.division_id = d.id AND lsr.status = "active") AS learner_count')
            ->from($this->table.' d')
            ->join('regions r', 'r.id = d.region_id', 'left')
            ->where('d.id', $id)
            ->limit(1)->get()->row();
    }
}
