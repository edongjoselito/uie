<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** All reads and writes require an explicit school; never fall back to all schools. */
class School_learner_model extends CI_Model
{
    public function listing($school_id, $year_id, $q = '', $grade = '', $program = '', $limit = 25, $offset = 0, $sort = 'name', $direction = 'asc')
    {
        $this->list_query($school_id, $year_id, $q, $grade, $program);
        $direction = $direction === 'desc' ? 'DESC' : 'ASC';
        if ($sort === 'grade')
        {
            $this->db->order_by("CASE WHEN e.grade_level = 'Kindergarten' THEN 0 WHEN e.grade_level LIKE 'Grade %' THEN CAST(SUBSTRING(e.grade_level, 7) AS UNSIGNED) ELSE 99 END ".$direction, '', FALSE);
        }
        elseif ($sort === 'lrn') $this->db->order_by('l.lrn', $direction);
        else $this->db->order_by('l.last_name', $direction)->order_by('l.first_name', $direction);
        return $this->db->order_by('e.id', 'ASC')->limit($limit, $offset)->get()->result();
    }

    public function count_rows($school_id, $year_id, $q = '', $grade = '', $program = '')
    {
        $this->list_query($school_id, $year_id, $q, $grade, $program);
        return (int) $this->db->count_all_results();
    }

    /** Counts each learner once within a group; program memberships may overlap. */
    public function report_summary($school_id, $year_id, $grade = '')
    {
        return $this->report_summary_scoped(array('school_id' => (int) $school_id), $year_id, $grade);
    }

    public function report_summary_scoped(array $scope, $year_id, $grade = '', $district = '')
    {
        $tags = $this->db->dbprefix('learner_program_tags');
        $conditions = array('all' => '1=1');
        foreach (array('sned', 'madrasah', 'iped', 'spfl') as $code)
            $conditions[$code] = "EXISTS (SELECT 1 FROM `$tags` t WHERE t.learner_school_record_id = e.id AND t.program = ".$this->db->escape($code).')';
        $conditions['multiple'] = "(SELECT COUNT(*) FROM `$tags` t WHERE t.learner_school_record_id = e.id) > 1";
        $conditions['untagged'] = "NOT EXISTS (SELECT 1 FROM `$tags` t WHERE t.learner_school_record_id = e.id)";
        foreach ($conditions as $key => $condition)
        {
            foreach (array('total' => '', 'male' => " AND l.sex = 'Male'", 'female' => " AND l.sex = 'Female'") as $metric => $sex)
                $this->db->select("COALESCE(SUM(CASE WHEN ($condition)$sex THEN 1 ELSE 0 END), 0) AS ".$key.'_'.$metric, FALSE);
        }
        $this->report_query_scoped($scope, $year_id, $grade, $district);
        $row = $this->db->get()->row_array();
        $summary = array();
        foreach ($conditions as $key => $condition)
            $summary[$key] = array('total' => (int) $row[$key.'_total'], 'male' => (int) $row[$key.'_male'], 'female' => (int) $row[$key.'_female']);
        return $summary;
    }

    public function report_count_scoped(array $scope, $year_id, $grade = '', $group = '', $district = '')
    {
        $this->report_query_scoped($scope, $year_id, $grade, $district, $group);
        return (int) $this->db->count_all_results();
    }

    public function report_listing_scoped(array $scope, $year_id, $grade = '', $group = '', $district = '', $limit = 25, $offset = 0)
    {
        $tags = $this->db->dbprefix('learner_program_tags');
        $this->db->select('l.*, s.name AS school_name, e.id AS enrollment_id, e.grade_level, e.section, e.enrollment_status, e.school_year_id, sy.year_label')
            ->select("(SELECT GROUP_CONCAT(t.program ORDER BY t.program) FROM `$tags` t WHERE t.learner_school_record_id = e.id) AS program_codes", FALSE);
        $this->report_query_scoped($scope, $year_id, $grade, $district, $group);
        return $this->db->order_by('s.name')->order_by('l.last_name')->order_by('l.first_name')
            ->limit($limit, $offset)->get()->result();
    }

    public function report_districts_scoped(array $scope)
    {
        $this->db->distinct()->select('s.municipality')->from('schools s')->where('s.status', 'active')
            ->where('s.municipality IS NOT NULL', NULL, FALSE)->where('s.municipality !=', '');
        foreach (array('region_id', 'division_id', 'school_id') as $key)
            if ( ! empty($scope[$key])) $this->db->where('s.'.$key, (int) $scope[$key]);
        return array_column($this->db->order_by('s.municipality')->get()->result_array(), 'municipality');
    }

    private function report_query_scoped(array $scope, $year_id, $grade = '', $district = '', $group = '')
    {
        $tags = $this->db->dbprefix('learner_program_tags');
        $this->db->from('learner_school_records e')
            ->join('learners l', 'l.id = e.learner_id')
            ->join('schools s', 's.id = e.school_id')
            ->join('school_years sy', 'sy.id = e.school_year_id')
            ->where('e.school_year_id', (int) $year_id)
            ->where('e.status', 'active')->where('l.status', 'active')->where('s.status', 'active');
        foreach (array('region_id', 'division_id', 'school_id') as $key)
            if ( ! empty($scope[$key])) $this->db->where('e.'.$key, (int) $scope[$key]);
        if ($grade !== '') $this->db->where('e.grade_level', $grade);
        if ($district !== '') $this->db->where('s.municipality', $district);
        if ($group === 'multiple')
            $this->db->where("(SELECT COUNT(*) FROM `$tags` mt WHERE mt.learner_school_record_id = e.id) > 1", NULL, FALSE);
        elseif ($group === 'untagged')
            $this->db->where("NOT EXISTS (SELECT 1 FROM `$tags` nt WHERE nt.learner_school_record_id = e.id)", NULL, FALSE);
        elseif ($group !== '' && $group !== 'all')
            $this->db->where("EXISTS (SELECT 1 FROM `$tags` ft WHERE ft.learner_school_record_id = e.id AND ft.program = ".$this->db->escape($group).')', NULL, FALSE);
    }

    public function year_overview($school_id, $year_id)
    {
        $tags = $this->db->dbprefix('learner_program_tags');
        $summary = $this->db->select('COUNT(*) AS total', FALSE)
            ->select("COALESCE(SUM(l.lrn IS NULL OR l.lrn = ''), 0) AS missing_lrn", FALSE)
            ->select("COALESCE(SUM((SELECT COUNT(*) FROM `$tags` t WHERE t.learner_school_record_id = e.id) > 1), 0) AS multiple_programs", FALSE)
            ->from('learner_school_records e')->join('learners l', 'l.id = e.learner_id')
            ->where('e.school_id', (int) $school_id)->where('e.school_year_id', (int) $year_id)
            ->where('e.status', 'active')->where('l.status', 'active')->get()->row_array();
        $summary['programs'] = array_fill_keys(array('iped', 'madrasah', 'sned', 'spfl'), 0);
        $counts = $this->db->select('t.program, COUNT(*) AS total', FALSE)->from('learner_program_tags t')
            ->join('learner_school_records e', 'e.id = t.learner_school_record_id')->join('learners l', 'l.id = e.learner_id')
            ->where('e.school_id', (int) $school_id)->where('e.school_year_id', (int) $year_id)
            ->where('e.status', 'active')->where('l.status', 'active')->group_by('t.program')->get()->result();
        foreach ($counts as $row) $summary['programs'][$row->program] = (int) $row->total;
        return $summary;
    }

    private function list_query($school_id, $year_id, $q, $grade, $program)
    {
        $tags = $this->db->dbprefix('learner_program_tags');
        $this->db->select('l.*, e.id AS enrollment_id, e.grade_level, e.section, e.enrollment_status, e.school_year_id, sy.year_label, sy.status AS year_status')
            ->select("(SELECT GROUP_CONCAT(t.program ORDER BY t.program) FROM `$tags` t WHERE t.learner_school_record_id = e.id) AS program_codes", FALSE)
            ->from('learner_school_records e')->join('learners l', 'l.id = e.learner_id')
            ->join('school_years sy', 'sy.id = e.school_year_id')
            ->where('e.school_id', (int) $school_id)->where('e.school_year_id', (int) $year_id)
            ->where('e.status', 'active')->where('l.status', 'active');
        if ($q !== '') $this->db->group_start()->like('l.lrn', $q)->or_like('l.last_name', $q)->or_like('l.first_name', $q)->group_end();
        if ($grade !== '') $this->db->where('e.grade_level', $grade);
        if ($program === 'multiple') $this->db->where("(SELECT COUNT(*) FROM `$tags` mt WHERE mt.learner_school_record_id = e.id) > 1", NULL, FALSE);
        elseif ($program === 'untagged') $this->db->where("NOT EXISTS (SELECT 1 FROM `$tags` nt WHERE nt.learner_school_record_id = e.id)", NULL, FALSE);
        elseif ($program !== '') $this->db->where("EXISTS (SELECT 1 FROM `$tags` ft WHERE ft.learner_school_record_id = e.id AND ft.program = ".$this->db->escape($program).')', NULL, FALSE);
    }

    public function enrollment($id, $school_id)
    {
        return $this->db->select('e.*, l.first_name, l.last_name, l.lrn, l.sex, l.date_of_birth')
            ->from('learner_school_records e')->join('learners l', 'l.id = e.learner_id')
            ->where('e.id', (int) $id)->where('e.school_id', (int) $school_id)
            ->where('e.status', 'active')->where('l.status', 'active')->get()->row();
    }

    public function tags($enrollment_id)
    {
        return array_column($this->db->where('learner_school_record_id', $enrollment_id)->get('learner_program_tags')->result_array(), 'program');
    }

    public function program_options()
    {
        return array(
            'ethnic_groups' => $this->db->where('status', 'active')->order_by('name')->get('iped_ethnic_groups')->result(),
            'cultural_communities' => $this->db->where('status', 'active')->order_by('name')->get('iped_cultural_communities')->result(),
            'madrasah_programs' => $this->db->where('status', 'active')->order_by('name')->get('madrasah_programs')->result(),
            'disability_categories' => $this->db->where('status', 'active')->order_by('name')->get('sned_disability_categories')->result(),
        );
    }

    public function program_profiles($enrollment_id)
    {
        $profiles = array();
        foreach (array('iped', 'madrasah', 'sned', 'spfl') as $program)
        {
            $profiles[$program] = (array) $this->db->where('learner_school_record_id', (int) $enrollment_id)
                ->get($program.'_learner_profiles')->row_array();
        }
        $profiles['madrasah']['program_ids'] = array_column(
            $this->db->select('mp.program_id')->from('madrasah_learner_programs mp')
                ->join('madrasah_learner_profiles p', 'p.id = mp.profile_id')
                ->where('p.learner_school_record_id', (int) $enrollment_id)->get()->result_array(),
            'program_id'
        );
        $profiles['sned']['disability_category_ids'] = array_column(
            $this->db->select('d.category_id')->from('sned_learner_disabilities d')
                ->join('sned_learner_profiles p', 'p.id = d.profile_id')
                ->where('p.learner_school_record_id', (int) $enrollment_id)->get()->result_array(),
            'category_id'
        );
        return $profiles;
    }

    public function known_learners($school_id)
    {
        return $this->db->distinct()->select('l.id, l.lrn, l.first_name, l.last_name')
            ->from('learners l')->join('learner_school_records e', 'e.learner_id = l.id')
            ->where('e.school_id', (int) $school_id)->where('e.status', 'active')->where('l.status', 'active')
            ->order_by('l.last_name, l.first_name')->get()->result();
    }

    /** Save an enrollment and its tags as one transaction, preserving other years. */
    public function save($school_id, $year_id, $learner_id, array $identity, ?array $placement, array $tags, $uid, $enrollment_id = NULL, array $profiles = array())
    {
        // A null placement updates only the tags on an existing enrollment.
        if ( ! $school_id || ! $year_id || ! $uid
            || ($placement === NULL && ! $enrollment_id)
            || ($placement !== NULL && ! in_array($placement['grade_level'] ?? NULL, self::grades(), TRUE))
            || array_diff($tags, array('sned', 'madrasah', 'iped', 'spfl'))) return FALSE;
        $this->db->trans_begin();
        $school = $this->db->where('id', $school_id)->where('status', 'active')->get('schools')->row();
        $years = $this->db->dbprefix('school_years');
        $year = $this->db->query("SELECT * FROM `$years` WHERE id = ? FOR UPDATE", array($year_id))->row();
        if ( ! $school || ! $year || $year->status !== 'open') return $this->fail();
        $old = NULL;
        if ($enrollment_id)
        {
            $old = $this->enrollment($enrollment_id, $school_id);
            if ( ! $old || (int) $old->school_year_id !== (int) $year_id) return $this->fail();
            $learner_id = $old->learner_id;
        }
        if ($learner_id)
        {
            // Re-enrollment may use only an identity already associated with this school.
            $known = $this->db->where('learner_id', $learner_id)->where('school_id', $school_id)
                ->where('status', 'active')->count_all_results('learner_school_records');
            $users = $this->db->dbprefix('learners');
            $learner = $this->db->query("SELECT * FROM `$users` WHERE id = ? FOR UPDATE", array($learner_id))->row();
            if ( ! $known || ! $learner || $learner->status !== 'active') return $this->fail();
        }
        else
        {
            // No global lookup information is returned to school users.
            if ( ! empty($identity['lrn']) && $this->db->where('lrn', $identity['lrn'])->count_all_results('learners')) return $this->fail();
            $identity = uppercase_learner_names($identity);
            $debug = $this->db->db_debug;
            $this->db->db_debug = FALSE;
            $inserted = $this->db->insert('learners', $identity + array('status' => 'active', 'created_by' => $uid, 'updated_by' => $uid));
            $this->db->db_debug = $debug;
            if ( ! $inserted) return $this->fail();
            $learner_id = $this->db->insert_id();
        }
        $existing = $this->db->where('learner_id', $learner_id)->where('school_id', $school_id)
            ->where('school_year_id', $year_id)->get('learner_school_records')->row();
        if ( ! $enrollment_id && $existing) return $this->fail();
        $data = array('updated_by' => $uid);
        if ($placement !== NULL)
        {
            $data += array('grade_level' => $placement['grade_level'], 'section' => $placement['section']);
        }
        if ($enrollment_id)
        {
            $old_tags = $this->tags($enrollment_id);
            $this->db->where('id', $enrollment_id)->where('school_id', $school_id)->update('learner_school_records', $data);
        }
        else
        {
            // Keep the latest chronological placement current, even when encoding an older year.
            $latest = $this->db->select('sy.start_year')->from('learner_school_records e')
                ->join('school_years sy', 'sy.id = e.school_year_id')->where('e.learner_id', $learner_id)
                ->where('e.status', 'active')->order_by('sy.start_year', 'DESC')->limit(1)->get()->row();
            $current = ! $latest || (int) $year->start_year >= (int) $latest->start_year;
            if ($current) $this->db->where('learner_id', $learner_id)->update('learner_school_records', array('is_current' => 0));
            $this->db->insert('learner_school_records', $data + array('learner_id' => $learner_id,
                'school_id' => $school_id, 'division_id' => $school->division_id, 'region_id' => $school->region_id,
                'school_year_id' => $year_id, 'is_current' => $current ? 1 : 0, 'created_by' => $uid));
            $enrollment_id = $this->db->insert_id();
            $old_tags = array();
        }
        $this->db->where('learner_school_record_id', $enrollment_id)->delete('learner_program_tags');
        foreach (array_unique($tags) as $tag) $this->db->insert('learner_program_tags', array('learner_school_record_id' => $enrollment_id, 'program' => $tag));
        if ( ! $this->save_program_profiles($enrollment_id, $learner_id, $year_id, $profiles, $uid)) return $this->fail();
        $this->db->insert('learner_record_history', array('learner_id' => $learner_id,
            'table_name' => 'learner_school_records', 'record_id' => $enrollment_id,
            'change_type' => $old ? 'update' : 'create', 'changed_by' => $uid,
            'old_values' => $old ? json_encode(array('grade_level' => $old->grade_level, 'section' => $old->section, 'programs' => $old_tags)) : NULL,
            'new_values' => json_encode($data + array('school_id' => $school_id, 'school_year_id' => $year_id, 'programs' => array_values(array_unique($tags))))));
        if ( ! $this->db->trans_status()) return $this->fail();
        $this->db->trans_commit();
        return $enrollment_id;
    }

    public function archive_enrollment($enrollment, $school_id, $uid)
    {
        if ( ! $this->db->trans_begin()) return FALSE;
        $years = $this->db->dbprefix('school_years');
        $year = $this->db->query("SELECT status FROM `$years` WHERE id = ? FOR UPDATE", array($enrollment->school_year_id))->row();
        if ( ! $year || $year->status !== 'open') return $this->fail();

        $learners = $this->db->dbprefix('learners');
        $learner = $this->db->query("SELECT status FROM `$learners` WHERE id = ? FOR UPDATE", array($enrollment->learner_id))->row();
        if ( ! $learner || $learner->status !== 'active') return $this->fail();

        $record = $this->enrollment($enrollment->id, $school_id);
        if ( ! $record || (int) $record->school_year_id !== (int) $enrollment->school_year_id
            || (int) $record->learner_id !== (int) $enrollment->learner_id) return $this->fail();

        $this->db->where('id', $record->id)->where('school_id', $school_id)
            ->update('learner_school_records', array('status' => 'archived', 'is_current' => 0, 'updated_by' => $uid));

        if ($record->is_current)
        {
            $next = $this->db->select('e.id')->from('learner_school_records e')
                ->join('school_years sy', 'sy.id = e.school_year_id')
                ->where('e.learner_id', $record->learner_id)->where('e.status', 'active')
                ->order_by('sy.start_year', 'DESC')->order_by('e.id', 'DESC')->limit(1)->get()->row();
            if ($next) $this->db->where('id', $next->id)->update('learner_school_records', array('is_current' => 1));
        }

        $this->db->insert('learner_record_history', array('learner_id' => $record->learner_id,
            'table_name' => 'learner_school_records', 'record_id' => $record->id, 'change_type' => 'archive',
            'old_values' => json_encode(array('status' => 'active', 'is_current' => (int) $record->is_current)),
            'new_values' => json_encode(array('status' => 'archived', 'is_current' => 0)), 'changed_by' => $uid));

        if ( ! $this->db->trans_status() || ! $this->db->trans_commit()) return $this->fail();
        return TRUE;
    }

    private function fail() { $this->db->trans_rollback(); return FALSE; }

    private function save_program_profiles($enrollment_id, $learner_id, $year_id, array $profiles, $uid)
    {
        $profile_tables = array(
            'iped' => array('icc_id', 'ethnic_group_id', 'tribe', 'indigenous_language', 'mother_tongue', 'ancestral_area', 'other_cultural_info', 'program_participation', 'educational_assistance', 'program_support', 'community_participation', 'remarks'),
            'madrasah' => array('alive_participation', 'arabic_proficiency', 'islamic_values_participation', 'years_in_program', 'participation_status', 'educational_assistance', 'program_support', 'remarks'),
            'sned' => array('functional_needs', 'assistive_devices', 'accessibility_requirements', 'iep_status', 'accommodations', 'assistive_technology', 'interventions', 'therapy_support', 'support_services', 'progress_monitoring', 'referral_info', 'doc_reference', 'last_profile_update', 'remarks'),
            'spfl' => array('foreign_language', 'proficiency_level', 'educational_assistance', 'program_support', 'remarks'),
        );
        foreach ($profile_tables as $program => $columns)
        {
            if ( ! isset($profiles[$program])) continue;
            $values = array_intersect_key($profiles[$program], array_flip($columns));
            $values['learner_id'] = (int) $learner_id;
            $values['learner_school_record_id'] = (int) $enrollment_id;
            $values['status'] = 'active';
            $values['updated_by'] = (int) $uid;
            $existing = $this->db->select('id')->where('learner_school_record_id', (int) $enrollment_id)
                ->get($program.'_learner_profiles')->row();
            if ($existing) $this->db->where('id', $existing->id)->update($program.'_learner_profiles', $values);
            else
            {
                $values['created_by'] = (int) $uid;
                $this->db->insert($program.'_learner_profiles', $values);
            }
            if ($this->db->trans_status() === FALSE) return FALSE;
            $profile_id = $existing ? (int) $existing->id : (int) $this->db->insert_id();

            if ($program === 'madrasah')
            {
                $program_ids = isset($profiles[$program]['program_ids']) ? $profiles[$program]['program_ids'] : array();
                if ($program_ids && $this->db->where_in('id', $program_ids)->where('status', 'active')->count_all_results('madrasah_programs') !== count(array_unique($program_ids))) return FALSE;
                $this->db->where('profile_id', $profile_id)->delete('madrasah_learner_programs');
                foreach (array_unique($program_ids) as $program_id)
                    $this->db->insert('madrasah_learner_programs', array('profile_id' => $profile_id, 'program_id' => (int) $program_id, 'school_year_id' => (int) $year_id));
            }
            elseif ($program === 'sned')
            {
                $category_ids = isset($profiles[$program]['disability_category_ids']) ? $profiles[$program]['disability_category_ids'] : array();
                if ($category_ids && $this->db->where_in('id', $category_ids)->where('status', 'active')->count_all_results('sned_disability_categories') !== count(array_unique($category_ids))) return FALSE;
                $this->db->where('profile_id', $profile_id)->delete('sned_learner_disabilities');
                foreach (array_unique($category_ids) as $index => $category_id)
                    $this->db->insert('sned_learner_disabilities', array('profile_id' => $profile_id, 'category_id' => (int) $category_id, 'is_primary' => $index === 0 ? 1 : 0));
            }
            if ($this->db->trans_status() === FALSE) return FALSE;
        }
        return TRUE;
    }

    public static function grades()
    {
        $grades = array('Kindergarten');
        for ($i = 1; $i <= 12; $i++) $grades[] = 'Grade '.$i;
        return $grades;
    }

    // ---------------------------------------------------------------
    // SF 1 (School Register) import
    // ---------------------------------------------------------------

    /** Identity columns an SF1 row may populate on the learner record. */
    public static function import_identity_fields()
    {
        return array('lrn', 'last_name', 'first_name', 'middle_name', 'extension_name', 'sex', 'date_of_birth',
            'place_of_birth', 'mother_tongue', 'ip_ethnicity', 'religion', 'address', 'barangay', 'municipality',
            'province', 'father_name', 'mother_name', 'guardian_name', 'guardian_relationship', 'contact_number', 'remarks');
    }

    /**
     * Read-only classification for the import preview.
     * @return array{status:string,message:string}
     */
    public function preview_status($school_id, $year_id, array $row)
    {
        $match = $this->match_import_learner($school_id, $row);
        if (isset($match['error'])) return array('status' => 'error', 'message' => $match['error']);
        $learner = $match['learner'];
        if ( ! $learner) return array('status' => 'create', 'message' => 'New learner profile.');
        $existing = $this->enrollment_for($learner->id, $school_id, $year_id);
        if ($existing) return array('status' => 'update', 'message' => 'Already enrolled this school year — placement and details will be refreshed.');
        return array('status' => 'enroll', 'message' => 'Existing school record — a new enrollment will be added.');
    }

    /**
     * Persist one normalized SF1 row: resolve the learner, save the
     * enrollment (grade/section/tags) through save(), then enrich the
     * learner identity with any non-empty SF1 fields.
     * @return array{result:string,message:string}
     */
    public function import_row($school_id, $year_id, array $row, $grade, $section, array $tags, $uid, array $profiles = array())
    {
        $match = $this->match_import_learner($school_id, $row);
        if (isset($match['error'])) return array('result' => 'error', 'message' => $match['error']);
        $learner = $match['learner'];
        $existing = NULL;
        $identity = array();
        if ($learner)
        {
            $existing = $this->enrollment_for($learner->id, $school_id, $year_id);
            // Re-imports keep the tags already recorded for the enrollment.
            if ($existing) $tags = array_values(array_unique(array_merge($this->tags($existing->id), $tags)));
        }
        else
        {
            foreach (self::import_identity_fields() as $field)
                $identity[$field] = isset($row[$field]) && $row[$field] !== '' ? $row[$field] : NULL;
        }
        $enrollment_id = $this->save($school_id, $year_id, $learner ? (int) $learner->id : 0, $identity,
            array('grade_level' => $grade, 'section' => $section === '' || $section === NULL ? NULL : $section),
            $tags, $uid, $existing ? (int) $existing->id : NULL, $profiles);
        if ( ! $enrollment_id)
        {
            return array('result' => 'error', 'message' => 'Unable to save this row. The school year may be closed or another import may have saved it first.');
        }
        if ($learner) $this->enrich_identity($learner, $row, $uid);
        return array('result' => $learner ? ($existing ? 'updated' : 'enrolled') : 'created', 'message' => '');
    }

    /**
     * Locate the learner an SF1 row refers to. LRN wins; without an LRN,
     * fall back to an exact name + birth-date match at this school.
     */
    private function match_import_learner($school_id, array $row)
    {
        $lrn = isset($row['lrn']) ? trim((string) $row['lrn']) : '';
        if ($lrn !== '')
        {
            $learner = $this->db->where('lrn', $lrn)->get('learners')->row();
            if ($learner)
            {
                if ($learner->status !== 'active')
                    return array('error' => 'LRN '.$lrn.' belongs to an archived learner record.');
                $known = $this->db->where('learner_id', $learner->id)->where('school_id', $school_id)
                    ->where('status', 'active')->count_all_results('learner_school_records');
                if ( ! $known)
                    return array('error' => 'LRN '.$lrn.' is registered at another school. Ask your administrator about a transfer.');
                if ($this->fold_name($learner->last_name) !== $this->fold_name($row['last_name'])
                    || $this->fold_name($learner->first_name) !== $this->fold_name($row['first_name']))
                    return array('error' => 'LRN '.$lrn.' is registered to '.$learner->last_name.', '.$learner->first_name.'. Verify the LRN or remove it from this row.');
                return array('learner' => $learner);
            }
        }
        if (empty($row['date_of_birth'])) return array('learner' => NULL);
        $learner = $this->db->select('l.*')->from('learners l')
            ->join('learner_school_records e', 'e.learner_id = l.id')
            ->where('e.school_id', (int) $school_id)->where('e.status', 'active')->where('l.status', 'active')
            ->where('l.last_name', $this->fold_name($row['last_name']))
            ->where('l.first_name', $this->fold_name($row['first_name']))
            ->where('l.date_of_birth', $row['date_of_birth'])
            ->order_by('e.id', 'DESC')->limit(1)->get()->row();
        return array('learner' => $learner);
    }

    private function enrollment_for($learner_id, $school_id, $year_id)
    {
        return $this->db->where('learner_id', (int) $learner_id)->where('school_id', (int) $school_id)
            ->where('school_year_id', (int) $year_id)->where('status', 'active')
            ->get('learner_school_records')->row();
    }

    /**
     * Apply non-empty SF1 fields onto an existing school learner without
     * blanking data. Names stay untouched (already verified); LRN is set
     * only when the record does not have one yet.
     */
    private function enrich_identity($learner, array $row, $uid)
    {
        $fields = array('middle_name', 'extension_name', 'sex', 'date_of_birth',
            'place_of_birth', 'mother_tongue', 'ip_ethnicity', 'religion', 'address', 'barangay',
            'municipality', 'province', 'father_name', 'mother_name', 'guardian_name',
            'guardian_relationship', 'contact_number');
        $update = array();
        foreach ($fields as $field)
        {
            if ( ! isset($row[$field])) continue;
            $value = trim((string) $row[$field]);
            if ($value === '') continue;
            if (in_array($field, array('middle_name', 'extension_name', 'father_name', 'mother_name', 'guardian_name'), TRUE))
                $value = function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
            if ($value !== (string) $learner->$field) $update[$field] = $value;
        }
        if (empty($learner->lrn) && ! empty($row['lrn'])) $update['lrn'] = $row['lrn'];
        if ( ! $update) return;
        $update['updated_by'] = (int) $uid;
        $this->db->where('id', $learner->id)->update('learners', $update);
        $this->db->insert('learner_record_history', array(
            'learner_id' => $learner->id, 'table_name' => 'learners', 'record_id' => $learner->id,
            'change_type' => 'update', 'changed_by' => (int) $uid,
            'old_values' => json_encode(array_intersect_key((array) $learner, $update)),
            'new_values' => json_encode($update),
        ));
    }

    /** Match an SF1 IP (ethnic group) cell to the IPEd ethnic groups list. */
    public function find_ethnic_group_id($name)
    {
        $name = trim((string) $name);
        if ($name === '') return NULL;
        $folded = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        foreach ($this->db->where('status', 'active')->get('iped_ethnic_groups')->result() as $group)
        {
            $current = function_exists('mb_strtolower') ? mb_strtolower($group->name, 'UTF-8') : strtolower($group->name);
            if ($current === $folded) return (int) $group->id;
        }
        return NULL;
    }

    private function fold_name($value)
    {
        $value = trim((string) $value);
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
