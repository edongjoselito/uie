<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__).'/';
define('BASEPATH', $root.'system/');
define('APPPATH', $root.'application/');
define('FCPATH', $root);
define('ENVIRONMENT', 'testing');
require BASEPATH.'core/Common.php';
require APPPATH.'config/constants.php';
require APPPATH.'config/database.php';
require BASEPATH.'database/DB.php';
class CI_Model { public $db; }
require APPPATH.'helpers/uie_helper.php';
require APPPATH.'models/School_learner_model.php';
require APPPATH.'libraries/Sf1_import.php';
require FCPATH.'vendor/autoload.php';
$model = new School_learner_model();
$model->db = DB($db['default'], TRUE);
$dbh = $model->db;
$parser = new Sf1_import();
function check($condition, $message) { if ( ! $condition) throw new RuntimeException($message); echo "PASS: $message\n"; }

// ---------------------------------------------------------------------
// Build a sample SF 1 workbook using the official two-row merged header.
// ---------------------------------------------------------------------
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('SF 1');
$sheet->setCellValue('A1', 'School Form 1 (SF 1) School Register');
$sheet->setCellValue('A2', 'Region: XI    Division: SDO Test    School Year: 7001-7002');
$sheet->setCellValue('A3', 'Grade Level: Grade 3    Section: Rizal');
$sheet->fromArray(array(
    'LRN', 'NAME', '', '', '', 'SEX', 'BIRTH DATE', 'AGE', 'MOTHER TONGUE', 'IP', 'RELIGION',
    'ADDRESS', '', '', '', 'PARENTS/GUARDIAN', '', '', '', 'CONTACT NUMBER', 'REMARKS',
), NULL, 'A4');
$sheet->fromArray(array(
    '', 'Last Name', 'First Name', 'Middle Name', 'Extension Name', '', '(mm/dd/yyyy)', '', '', '(Ethnic Group)', '',
    'House No./Street/Sitio', 'Barangay', 'Municipality/City', 'Province',
    "Father's Name", "Mother's Maiden Name", "Guardian's Name", 'Relationship', '', '',
), NULL, 'A5');
$sheet->mergeCells('B4:E4')->mergeCells('K4:N4')->mergeCells('O4:R4');
$sheet->fromArray(array(
    array('106123450001', 'DELA CRUZ', 'JUAN', 'SANTOS', '', 'M', '01/15/2016', 9, 'Cebuano', 'Manobo', 'Catholic', 'Purok 1', 'Poblacion', 'Davao City', 'Davao del Sur', 'DELA CRUZ, PEDRO', 'SANTOS, MARIA', '', '', '09171234567', ''),
    array('106123450002', 'PEÑA', 'Rosa', 'MUÑOZ', '', 'F', '2016-03-20', 9, 'Filipino', '', 'Christian', 'Blk 2', 'Matina', 'Davao City', 'Davao del Sur', '', 'MUÑOZ, ANA', 'MUÑOZ, ANA', 'Mother', '09179998877', ''),
    array('', 'BAUTISTA', 'Mark', '', 'JR', 'male', '11/30/2015', 10, '', '', '', '', '', '', '', '', '', '', '', '', 'No LRN row'),
    array('12345', 'BADROW', 'Incomplete', '', '', 'X', 'not a date', '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
    array('106123450001', 'DELA CRUZ', 'JUAN', 'SANTOS', '', 'M', '01/15/2016', 9, '', '', '', '', '', '', '', '', '', '', '', '', 'Duplicate in file'),
), NULL, 'A6');
$tmp = tempnam(sys_get_temp_dir(), 'sf1');
$path = $tmp.'.xlsx';
(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
$spreadsheet->disconnectWorksheets();

$parsed = $parser->parse($path);
check($parsed['ok'], 'Sample SF 1 workbook parsed');
check($parsed['meta']['grade'] === 'Grade 3' && $parsed['meta']['section'] === 'Rizal', 'Grade and section detected from the header block');
check(count($parsed['rows']) === 5, 'Five data rows detected');
$r1 = $parsed['rows'][0]['fields'];
check($r1['lrn'] === '106123450001' && $r1['last_name'] === 'DELA CRUZ' && $r1['first_name'] === 'JUAN'
    && $r1['sex'] === 'Male' && $r1['date_of_birth'] === '2016-01-15' && $r1['mother_tongue'] === 'Cebuano'
    && $r1['ip_ethnicity'] === 'Manobo' && $r1['religion'] === 'Catholic' && $r1['barangay'] === 'Poblacion'
    && $r1['municipality'] === 'Davao City' && $r1['father_name'] === 'DELA CRUZ, PEDRO'
    && $r1['guardian_relationship'] === '' && $r1['contact_number'] === '09171234567',
    'Row 1 maps every SF 1 column');
$r3 = $parsed['rows'][2]['fields'];
check($r3['lrn'] === '' && $r3['extension_name'] === 'JR' && $r3['sex'] === 'Male' && $r3['date_of_birth'] === '2015-11-30',
    'Lowercase sex, no LRN and extension name normalized');
check( ! $parsed['rows'][3]['valid'], 'Row with short LRN, bad sex and bad date flagged invalid');
check( ! $parsed['rows'][4]['valid'], 'In-file LRN duplicate flagged invalid');

// Flat single-row header also parses.
$flat = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$fs = $flat->getActiveSheet();
$fs->fromArray(array('LRN', 'Last Name', 'First Name', 'Middle Name', 'Extension Name', 'Sex',
    'Birth Date (mm/dd/yyyy)', 'Mother Tongue', 'IP (Ethnic Group)', 'Religion',
    'House No./Street/Sitio', 'Barangay', 'Municipality/City', 'Province',
    "Father's Name", "Mother's Maiden Name", "Guardian's Name", 'Relationship', 'Contact Number', 'Remarks'), NULL, 'A1');
$fs->fromArray(array('106123450003', 'SINGLE', 'Header', '', '', 'F', '02/14/2017', 'Tagalog', '', 'INC', 'St 1', 'Brgy', 'Tagum', 'DN', '', '', '', 'Guardian', '', ''), NULL, 'A2');
$flat_path = $tmp.'_flat.xlsx';
(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($flat))->save($flat_path);
$flat->disconnectWorksheets();
$flat_parsed = $parser->parse($flat_path);
check($flat_parsed['ok'] && count($flat_parsed['rows']) === 1 && $flat_parsed['rows'][0]['fields']['guardian_relationship'] === 'Guardian',
    'Flat single-row header parses');

// ---------------------------------------------------------------------
// Import into a temporary school year and verify records.
// ---------------------------------------------------------------------
$schools = $dbh->where('status', 'active')->limit(2)->get('schools')->result();
check(count($schools) === 2, 'Two schools available for isolation checks');
$uid = (int) $dbh->select('id')->limit(1)->get('users')->row('id');
$school = $schools[0]->id;
$other = $schools[1]->id;
$year_id = NULL;
$learners = array();
try {
    $start = random_int(7100, 8500);
    $dbh->insert('school_years', array('year_label' => $start.'-'.($start + 1), 'start_year' => $start, 'end_year' => $start + 1, 'status' => 'open'));
    $year_id = $dbh->insert_id();

    // Seed an existing learner at another school to exercise the LRN isolation rule.
    $dbh->insert('learners', array('lrn' => '106123450009', 'last_name' => 'FOREIGN', 'first_name' => 'SCHOOL',
        'sex' => 'Female', 'status' => 'active', 'created_by' => $uid, 'updated_by' => $uid));
    $foreign_id = $dbh->insert_id();
    $dbh->insert('learner_school_records', array('learner_id' => $foreign_id, 'school_id' => $other,
        'division_id' => $schools[1]->division_id, 'region_id' => $schools[1]->region_id,
        'school_year_id' => $year_id, 'grade_level' => 'Grade 1', 'is_current' => 1,
        'created_by' => $uid, 'updated_by' => $uid));
    $learners[] = $foreign_id;

    $status = $model->preview_status($school, $year_id, $parsed['rows'][0]['fields']);
    check($status['status'] === 'create', 'Preview reports a new learner');
    $result = $model->import_row($school, $year_id, $parsed['rows'][0]['fields'], 'Grade 3', 'Rizal', array('iped'), $uid,
        array('iped' => array('ethnic_group_id' => (int) $dbh->select('id')->where('status', 'active')->limit(1)->get('iped_ethnic_groups')->row('id'))));
    check($result['result'] === 'created', 'Row 1 creates learner + enrollment');
    $enrollment = $model->enrollment($dbh->select('id')->where('lrn', '106123450001')->get('learner_school_records')->row('id'), $school);
    check($enrollment && $enrollment->grade_level === 'Grade 3' && $enrollment->section === 'Rizal', 'Enrollment placement saved');
    $lid = $enrollment->learner_id;
    $learners[] = $lid;
    $stored = $dbh->where('id', $lid)->get('learners')->row();
    check($stored->mother_tongue === 'Cebuano' && $stored->ip_ethnicity === 'Manobo' && $stored->religion === 'Catholic'
        && $stored->barangay === 'Poblacion' && $stored->father_name === 'DELA CRUZ, PEDRO',
        'SF 1 identity columns persisted');
    check($model->tags($enrollment->id) === array('iped'), 'IPEd tag applied on import');
    check($model->program_profiles($enrollment->id)['iped']['ethnic_group_id'] !== NULL, 'IPEd ethnic group linked');

    // Re-import the same row: updates enrollment, keeps tags, no duplicates.
    $result = $model->import_row($school, $year_id, $parsed['rows'][0]['fields'], 'Grade 3', 'NewSection', array(), $uid);
    check($result['result'] === 'updated', 'Re-import refreshes the existing enrollment');
    $enrollment = $model->enrollment($enrollment->id, $school);
    check($enrollment->section === 'NewSection' && $model->tags($enrollment->id) === array('iped'), 'Section updated, existing tag preserved');
    check($dbh->where('learner_id', $lid)->count_all_results('learner_school_records') === 1, 'No duplicate enrollment created');

    // Second learner enrolled next year via name+DOB match is not exercised here;
    // a row with no LRN creates a new learner and matches on re-import.
    $result = $model->import_row($school, $year_id, $parsed['rows'][2]['fields'], 'Grade 3', 'Rizal', array(), $uid);
    check($result['result'] === 'created', 'Row without LRN creates a learner');
    $no_lrn_id = $dbh->select('id')->where('lrn IS NULL', NULL, FALSE)->where('last_name', 'BAUTISTA')->get('learners')->row('id');
    $learners[] = $no_lrn_id;
    $status = $model->preview_status($school, $year_id, $parsed['rows'][2]['fields']);
    check($status['status'] === 'update', 'Name + birth-date match detected on re-import preview');

    // Guard rails: LRN registered at another school and name/LRN conflicts are rejected.
    $conflict = array_merge($parsed['rows'][0]['fields'], array('lrn' => '106123450009', 'last_name' => 'FOREIGN', 'first_name' => 'SCHOOL'));
    $result = $model->import_row($school, $year_id, $conflict, 'Grade 3', 'Rizal', array(), $uid);
    check($result['result'] === 'error', 'LRN registered at another school is rejected');
    $mismatch = array_merge($parsed['rows'][0]['fields'], array('first_name' => 'DifferentName'));
    $result = $model->import_row($school, $year_id, $mismatch, 'Grade 3', 'Rizal', array(), $uid);
    check($result['result'] === 'error', 'Name/LRN mismatch is rejected');

    // Enrichment never blanks stored values.
    $sparse = array_merge($parsed['rows'][0]['fields'], array('mother_tongue' => '', 'religion' => '', 'barangay' => ''));
    $result = $model->import_row($school, $year_id, $sparse, 'Grade 3', 'Rizal', array(), $uid);
    $stored = $dbh->where('id', $lid)->get('learners')->row();
    check($stored->mother_tongue === 'Cebuano' && $stored->religion === 'Catholic' && $stored->barangay === 'Poblacion',
        'Empty SF 1 cells do not erase stored identity fields');
    check($result['result'] === 'updated', 'Sparse re-import still updates enrollment');
} finally {
    if ($learners)
    {
        $enrollment_ids = array_column($dbh->select('id')->where_in('learner_id', $learners)->get('learner_school_records')->result_array(), 'id');
        if ($enrollment_ids)
        {
            foreach (array('iped', 'madrasah', 'sned', 'spfl') as $program)
                $dbh->where_in('learner_school_record_id', $enrollment_ids)->delete($program.'_learner_profiles');
        }
    }
    foreach ($learners as $lid)
    {
        $dbh->where('learner_id', $lid)->delete('learner_record_history');
        $dbh->where('learner_id', $lid)->delete('learner_school_records');
        $dbh->where('id', $lid)->delete('learners');
    }
    if ($year_id) $dbh->where('id', $year_id)->delete('school_years');
    @unlink($path);
    @unlink($flat_path);
    @unlink($tmp);
}
echo "All SF 1 import checks passed.\n";
