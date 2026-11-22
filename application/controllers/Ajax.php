<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared AJAX endpoints for dependent dropdowns.
 * Scoped: non-superadmin users can only query within their own org.
 */
class Ajax extends Admin_Controller
{
    public function divisions_by_region($region_id = 0)
    {
        $region_id = (int) $region_id;
        $scope = $this->access_control->scope_region_id();
        if ($scope !== NULL && (int) $scope !== $region_id)
        {
            return $this->json(array('error' => 'Forbidden'), 403);
        }

        $this->load->model('Division_model');
        $rows = $this->Division_model->options_by_region($region_id);
        $out = array();
        foreach ($rows as $r)
        {
            $out[] = array('id' => (int) $r->id, 'name' => $r->name);
        }
        return $this->json($out);
    }

    public function schools_by_division($division_id = 0)
    {
        $division_id = (int) $division_id;
        $scope = $this->access_control->scope_division_id();
        if ($scope !== NULL && (int) $scope !== $division_id)
        {
            return $this->json(array('error' => 'Forbidden'), 403);
        }

        $this->load->model('School_model');
        $rows = $this->School_model->options_by_division($division_id);
        $out = array();
        foreach ($rows as $r)
        {
            $out[] = array('id' => (int) $r->id, 'school_id' => $r->school_id, 'name' => $r->name);
        }
        return $this->json($out);
    }
}
