<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__).'/';
define('BASEPATH', $root.'system/');
define('APPPATH', $root.'application/');
define('ENVIRONMENT', 'testing');
require BASEPATH.'core/Common.php';
require APPPATH.'config/constants.php';
require APPPATH.'config/database.php';
require BASEPATH.'database/DB.php';
class CI_Model { public $db; }
require APPPATH.'helpers/uie_helper.php';
require APPPATH.'models/School_learner_model.php';
$model = new School_learner_model();
$model->db = DB($db['default'], TRUE);
$dbh = $model->db;
function check($condition, $message) { if ( ! $condition) throw new RuntimeException($message); echo "PASS: $message\n"; }
$schools = $dbh->where('status', 'active')->limit(2)->get('schools')->result();
check(count($schools) === 2, 'Two schools available for isolation checks');
$uid = (int) $dbh->select('id')->limit(1)->get('users')->row('id');
$school = $schools[0]->id;
$other = $schools[1]->id;
$years = array(); $learners = array();
$suffix = bin2hex(random_bytes(5));
try {
    $start = random_int(7000, 8500);
    for ($i = 0; $i < 3; $i++) {
        $y = $start + $i;
        $dbh->insert('school_years', array('year_label' => $y.'-'.($y+1), 'start_year' => $y, 'end_year' => $y+1, 'status' => $i === 2 ? 'closed' : 'open'));
        $years[] = $dbh->insert_id();
    }
    $identity = array('first_name' => 'TestJosé'.$suffix, 'last_name' => 'PeñaEncoding', 'middle_name' => 'Muñoz', 'sex' => 'Female', 'date_of_birth' => '2015-01-01');
    $ethnic_id = (int) $dbh->select('id')->where('status', 'active')->limit(1)->get('iped_ethnic_groups')->row('id');
    $community_id = (int) $dbh->select('id')->where('status', 'active')->limit(1)->get('iped_cultural_communities')->row('id');
    $madrasah_program_id = (int) $dbh->select('id')->where('status', 'active')->limit(1)->get('madrasah_programs')->row('id');
    $disability_id = (int) $dbh->select('id')->where('status', 'active')->limit(1)->get('sned_disability_categories')->row('id');
    $profiles = array(
        'iped' => array('ethnic_group_id' => $ethnic_id, 'icc_id' => $community_id, 'tribe' => 'Test Tribe'),
        'madrasah' => array('alive_participation' => 'Yes', 'years_in_program' => 2, 'program_ids' => array($madrasah_program_id)),
        'sned' => array('iep_status' => 'Under Review', 'disability_category_ids' => array($disability_id)),
        'spfl' => array('foreign_language' => 'Japanese', 'proficiency_level' => 'Beginning'),
    );
    $e1 = $model->save($school, $years[0], 0, $identity, array('grade_level' => 'Grade 1', 'section' => 'A'), array('sned','iped','madrasah','spfl'), $uid, NULL, $profiles);
    check((bool) $e1, 'New learner enrolled with multiple tags and profiles');
    $record = $model->enrollment($e1, $school); $lid = $record->learner_id; $learners[] = $lid;
    check(count($model->tags($e1)) === 4, 'Multiple tags persisted');
    $stored_profiles = $model->program_profiles($e1);
    check($stored_profiles['iped']['tribe'] === 'Test Tribe' && (int) $stored_profiles['madrasah']['years_in_program'] === 2
        && $stored_profiles['sned']['iep_status'] === 'Under Review' && $stored_profiles['spfl']['foreign_language'] === 'Japanese',
        'Program profile details persist for all selected programs');
    check(count($stored_profiles['madrasah']['program_ids']) === 1 && count($stored_profiles['sned']['disability_category_ids']) === 1,
        'Madrasah programs and SNED support categories persist');
    $overview = $model->year_overview($school, $years[0]);
    check((int) $overview['total'] === 1 && (int) $overview['multiple_programs'] === 1 && (int) $overview['missing_lrn'] === 1 && $overview['programs']['sned'] === 1 && $overview['programs']['iped'] === 1, 'Overview counts learners once and counts each applicable program');
    check((int) $model->year_overview($other, $years[0])['total'] === 0 && (int) $model->year_overview($school, $years[1])['total'] === 0, 'Overview remains isolated by school and year');
    $report = $model->report_summary($school, $years[0]);
    check($report['all']['total'] === 1 && $report['all']['female'] === 1 && $report['sned']['total'] === 1 && $report['iped']['total'] === 1 && $report['multiple']['total'] === 1 && $report['untagged']['total'] === 0, 'Report counts unique learners, sex, and overlapping groups correctly');
    check($model->report_summary($other, $years[0])['all']['total'] === 0 && $model->report_summary($school, $years[1])['all']['total'] === 0 && $model->report_summary($school, $years[0], 'Grade 12')['all']['total'] === 0, 'Reports enforce school, year, and grade filters');
    $division_scope = array('division_id' => (int) $schools[0]->division_id);
    $region_scope = array('region_id' => (int) $schools[0]->region_id);
    check($model->report_summary_scoped($division_scope, $years[0])['all']['total'] === 1
        && $model->report_summary_scoped($region_scope, $years[0])['all']['total'] === 1,
        'Division and regional report summaries stay within their assigned scope');
    $scoped_rows = $model->report_listing_scoped($division_scope, $years[0], '', 'sned', '', 25, 0);
    check(count($scoped_rows) === 1 && $scoped_rows[0]->school_name === $schools[0]->name,
        'Division program detail reports include the school');
    if ($schools[0]->municipality) {
        check($model->report_summary_scoped($division_scope, $years[0], '', $schools[0]->municipality)['all']['total'] === 1
            && $model->report_summary_scoped($division_scope, $years[0], '', 'not-a-district')['all']['total'] === 0,
            'District report filters use the selected school municipality');
    }
    check(count($model->listing($school, $years[0], '', '', 'multiple')) === 1 && count($model->listing($school, $years[0], '', '', 'untagged')) === 0, 'Detailed report groups match summary totals');


    $stored = $dbh->where('id', $lid)->get('learners')->row();
    check($stored->middle_name === 'MUÑOZ', 'Accented middle name saved in uppercase');
    check($record->first_name === mb_strtoupper($identity['first_name'], 'UTF-8') && $record->last_name === 'PEÑAENCODING', 'Mixed-case learner names saved in uppercase');
    check( ! $model->enrollment($e1, $other), 'Other school cannot read enrollment');
    check($model->count_rows($other, $years[0]) === 0, 'Other school list excludes learner');
    check( ! $model->save($other, $years[0], $lid, array(), array('grade_level' => 'Grade 3', 'section' => NULL), array('madrasah'), $uid, $e1), 'Other school cannot edit enrollment');
    check( ! $model->save($other, $years[1], $lid, array(), array('grade_level' => 'Grade 3', 'section' => NULL), array(), $uid), 'Other school cannot enroll a forged learner ID');
    $e2 = $model->save($school, $years[1], $lid, array(), array('grade_level' => 'Grade 2', 'section' => 'B'), array('madrasah','spfl'), $uid);
    check((bool) $e2, 'Same learner enrolled in another year');
    check($model->enrollment($e1, $school)->grade_level === 'Grade 1' && count($model->tags($e1)) === 4, 'Earlier year grade and tags preserved');
    check($model->count_rows($school, $years[0], '', '', 'sned') === 1 && $model->count_rows($school, $years[1], '', '', 'sned') === 0, 'Program filtering is specific to year');
    check(count($model->listing($school, $years[1], '', 'Grade 2', 'spfl')) === 1, 'Grade and program filters combine');
    check( ! $model->save($school, $years[1], $lid, array(), array('grade_level' => 'Grade 2', 'section' => NULL), array(), $uid), 'Duplicate enrollment rejected');
    check( ! $model->save($school, $years[2], $lid, array(), array('grade_level' => 'Grade 3', 'section' => NULL), array(), $uid), 'Closed year rejects encoding');
    check( ! $model->save($school, $years[1], $lid, array(), array('grade_level' => 'Grade 2', 'section' => NULL), array('fake'), $uid, $e2), 'Unknown program rejected');
    check( ! $model->save($school, $years[1], $lid, array(), array('grade_level' => 'Grade 99', 'section' => NULL), array(), $uid, $e2), 'Unknown grade rejected');
    check((bool) $model->save($school, $years[1], $lid, array(), array('grade_level' => 'Grade 2', 'section' => 'C'), array(), $uid, $e2), 'Enrollment editable and tags removable');
    check($model->tags($e2) === array(), 'Removed tags no longer persisted');
    check($model->report_summary($school, $years[1])['untagged']['total'] === 1 && count($model->listing($school, $years[1], '', '', 'untagged')) === 1, 'Learners without tags appear in the untagged report');
    check((int) $model->enrollment($e1, $school)->is_current === 0 && (int) $model->enrollment($e2, $school)->is_current === 1, 'Latest year remains current');
    check($dbh->where('learner_id', $lid)->count_all_results('learner_record_history') === 3, 'Enrollment changes recorded in history');
    $backfill = $model->save($school, $years[1], 0, array('first_name' => 'Backfill'.$suffix, 'last_name' => 'SchoolEncoding', 'sex' => 'Male'), array('grade_level' => 'Grade 2', 'section' => NULL), array('sned'), $uid);
    $backfill_id = $model->enrollment($backfill, $school)->learner_id; $learners[] = $backfill_id;
    $older = $model->save($school, $years[0], $backfill_id, array(), array('grade_level' => 'Grade 1', 'section' => NULL), array('iped'), $uid);
    check((int) $model->enrollment($backfill, $school)->is_current === 1 && (int) $model->enrollment($older, $school)->is_current === 0, 'Backfilling older year preserves latest current placement');
    $higher = $model->save($school, $years[1], 0, array('first_name' => 'Sorting'.$suffix, 'last_name' => 'SchoolEncoding', 'sex' => 'Female'), array('grade_level' => 'Grade 10', 'section' => NULL), array(), $uid);
    $learners[] = $model->enrollment($higher, $school)->learner_id;
    $ascending = $model->listing($school, $years[1], '', '', '', 25, 0, 'grade', 'asc');
    $descending = $model->listing($school, $years[1], '', '', '', 25, 0, 'grade', 'desc');
    check($ascending[0]->grade_level === 'Grade 2' && end($ascending)->grade_level === 'Grade 10' && $descending[0]->grade_level === 'Grade 10', 'Grade sorting uses numeric grade order in both directions');
    check(count($model->listing($school, $years[1], '', '', '', 1, 1)) === 1, 'Pagination limits rows and supports offsets');
} finally {
    if ($learners) {
        $enrollment_ids = array_column($dbh->select('id')->where_in('learner_id', $learners)->get('learner_school_records')->result_array(), 'id');
        if ($enrollment_ids) {
            foreach (array('iped', 'madrasah', 'sned', 'spfl') as $program)
                $dbh->where_in('learner_school_record_id', $enrollment_ids)->delete($program.'_learner_profiles');
        }
    }
    foreach ($learners as $lid) {
        $dbh->where('learner_id', $lid)->delete('learner_record_history');
        $dbh->where('learner_id', $lid)->delete('learner_school_records');
        $dbh->where('id', $lid)->delete('learners');
    }
    if ($years) $dbh->where_in('id', $years)->delete('school_years');
}
