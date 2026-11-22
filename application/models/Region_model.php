<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Region_model extends MY_Model
{
    protected $table = 'regions';
    protected $default_order = 'name ASC';

    public function active_options()
    {
        return $this->db->where('status', 'active')->order_by('name')->get($this->table)->result();
    }

    /**
     * Regions with division/school/learner counts for dashboard + list.
     */
    public function with_stats(array $filters = array())
    {
        if ( ! empty($filters['q']))
        {
            $this->db->group_start()
                ->like('r.name', $filters['q'])
                ->or_like('r.code', $filters['q'])
                ->group_end();
        }
        if ( ! empty($filters['status'])) $this->db->where('r.status', $filters['status']);

        return $this->db->select('r.*,
                (SELECT COUNT(*) FROM uie_divisions d WHERE d.region_id = r.id) AS division_count,
                (SELECT COUNT(*) FROM uie_schools s WHERE s.region_id = r.id) AS school_count,
                (SELECT COUNT(DISTINCT lsr.learner_id) FROM uie_learner_school_records lsr WHERE lsr.region_id = r.id AND lsr.status = "active") AS learner_count')
            ->from($this->table.' r')
            ->order_by('r.name')
            ->get()->result();
    }

    public function detail($id)
    {
        return $this->db->select('r.*,
                (SELECT COUNT(*) FROM uie_divisions d WHERE d.region_id = r.id) AS division_count,
                (SELECT COUNT(*) FROM uie_schools s WHERE s.region_id = r.id) AS school_count')
            ->from($this->table.' r')
            ->where('r.id', $id)
            ->limit(1)->get()->row();
    }
}
