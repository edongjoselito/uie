<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class School_year_model extends MY_Model
{
    protected $table = 'school_years';
    protected $default_order = 'start_year DESC';

    public function options()
    {
        return $this->db->order_by('start_year', 'DESC')->get($this->table)->result();
    }

    public function active()
    {
        return $this->db->where('is_active', 1)->limit(1)->get($this->table)->row();
    }

    /**
     * Mark one school year active; all others inactive. Transactional.
     */
    public function set_active($id)
    {
        $this->db->trans_start();
        $this->db->update($this->table, array('is_active' => 0));
        $this->db->where('id', $id)->update($this->table, array('is_active' => 1, 'updated_by' => $this->_uid()));
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function label_exists($label, $exclude_id = NULL)
    {
        return $this->exists(array('year_label' => $label), $exclude_id);
    }
}
