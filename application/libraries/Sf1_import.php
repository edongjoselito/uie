<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DepEd School Form 1 (School Register) import parser.
 *
 * Reads .xlsx/.xls/.csv workbooks, detects the SF1 column header block
 * (single-row or the official two-row merged layout), maps columns to
 * learner registry fields and normalizes each row for review/import.
 */
class Sf1_import
{
    const MAX_ROWS = 500;

    /** Canonical field => list of normalized header labels. */
    public static function column_aliases()
    {
        return array(
            'lrn' => array('lrn', 'learnerreferencenumber', 'learnersreferencenumber', 'lrnno', 'lrnnumber'),
            'last_name' => array('lastname', 'surname', 'familyname', 'learnerlastname', 'apeyido', 'apelyido'),
            'first_name' => array('firstname', 'givenname', 'learnerfirstname', 'pangalan'),
            'middle_name' => array('middlename', 'learnermiddlename', 'gitnangpangalan', 'mi'),
            'extension_name' => array('extensionname', 'nameextension', 'extension', 'extname', 'suffixname', 'suffix'),
            'full_name' => array('name', 'learnername', 'learnersname', 'nameoflearner', 'fullname'),
            'sex' => array('sex', 'gender', 'mf', 'mfemale', 'kasarian'),
            'date_of_birth' => array('birthdate', 'birthdatemmddyyyy', 'dateofbirth', 'dob', 'birthday', 'birthdatemmddyy', 'petsangkapanganakan', 'kapanganakan'),
            'mother_tongue' => array('mothertongue', 'mt', 'mothertonguelanguage', 'wikangginagamitsabahay'),
            'ip_ethnicity' => array('ip', 'ipethnicgroup', 'ipethnicity', 'ethnicgroup', 'indigenouspeoples', 'ipeethnicgroup', 'ipgroup', 'ethnicity'),
            'religion' => array('religion', 'religiousaffiliation', 'relihiyon'),
            'address' => array('housenostreetsitio', 'housenostreetsitiopurok', 'housestreet', 'housenostreet', 'housenumberstreet', 'street', 'sitio', 'address', 'addressstreet', 'housenostreetbarangay'),
            'barangay' => array('barangay', 'brgy', 'baranggay'),
            'municipality' => array('municipalitycity', 'municipality', 'citymunicipality', 'towncity', 'town', 'city', 'mun'),
            'province' => array('province', 'lalawigan'),
            'father_name' => array('fathersname', 'fathername', 'father', 'fathersnamelastfirstmiddle', 'fatherfullname'),
            'mother_name' => array('mothersmaidenname', 'mothersname', 'mothername', 'mothermaidenname', 'maidenname', 'mother', 'mothersmaidennameklastfirstmiddle', 'motherfullname'),
            'guardian_name' => array('guardianname', 'guardiansname', 'nameofguardian', 'guardian', 'guardiansfullname', 'guardiannameifnotparents'),
            'contact_number' => array('contactnumber', 'contactnumberofparentguardian', 'contactnumberofparentorguardian', 'contactno', 'contact', 'contactnumberofguardian', 'telephonenumber', 'mobilenumber', 'cellnumber', 'contactnumberofparent', 'guardiancontactnumber'),
            'guardian_relationship' => array('relationship', 'guardianrelationship', 'relationshiptothelearner', 'rel'),
            'remarks' => array('remarks', 'remark', 'notes', 'indicators'),
        );
    }

    /**
     * Parse a workbook into normalized learner rows.
     *
     * @return array{ok:bool,error:?string,meta:array,rows:array}
     */
    public function parse($filepath)
    {
        $grid = $this->read_grid($filepath);
        if ($grid === FALSE)
        {
            return $this->error('The uploaded file is not a readable spreadsheet. Upload the official SF 1 Excel file (.xlsx, .xls, or .csv).');
        }

        $found = $this->find_header($grid);
        if ( ! $found)
        {
            return $this->error('No SF 1 header row was found. The sheet should contain columns named LRN and learner name / birth date. Download the template for the expected layout.');
        }
        list($header_row, $map) = $found;
        if ( ! isset($map['last_name']) && ! isset($map['first_name']) && ! isset($map['full_name']))
        {
            return $this->error('The learner name columns could not be identified. Keep the SF 1 column headers (Last Name, First Name, Middle Name, Extension Name).');
        }
        if ( ! isset($map['sex']) || ! isset($map['date_of_birth']))
        {
            return $this->error('The Sex and Birth Date columns are required. Keep the standard SF 1 column headers.');
        }

        $meta = $this->detect_meta($grid, $header_row);
        $meta['header_row'] = $header_row + 1; // 1-based for display
        $meta['columns'] = $map;

        $rows = array();
        $seen = array();
        $labels = array();
        foreach (self::column_aliases() as $field => $aliases) foreach ($aliases as $alias) $labels[$alias] = TRUE;
        $limit = min(count($grid), $header_row + 1 + self::MAX_ROWS);
        for ($r = $header_row + 1; $r < $limit; $r++)
        {
            $raw = isset($grid[$r]) ? $grid[$r] : array();
            if ($this->blank($raw)) continue;
            $row = $this->normalize_row($raw, $map, $r + 1);
            if ($row === NULL) continue; // repeated header/footer text
            $key = $row['fields']['lrn'] !== '' ? 'lrn:'.$row['fields']['lrn']
                : 'name:'.$this->fold($row['fields']['last_name']).'|'.$this->fold($row['fields']['first_name']).'|'.(string) $row['fields']['date_of_birth'];
            if (isset($seen[$key]))
            {
                $row['valid'] = FALSE;
                $row['errors'][] = 'Duplicate of row '.$seen[$key].' in this file.';
            }
            $seen[$key] = $r + 1;
            $rows[] = $row;
        }
        if ( ! $rows)
        {
            return $this->error('No learner rows were found below the SF 1 headers.');
        }
        $overflow = count($grid) - 1 - $header_row - count($rows);
        if ($overflow > self::MAX_ROWS || count($rows) >= self::MAX_ROWS)
        {
            return $this->error('The file exceeds the '.self::MAX_ROWS.' row limit. Split the register into smaller files.');
        }
        return array('ok' => TRUE, 'error' => NULL, 'meta' => $meta, 'rows' => $rows);
    }

    // ---------------------------------------------------------------
    // Workbook reading
    // ---------------------------------------------------------------

    private function read_grid($filepath)
    {
        require_once FCPATH.'vendor/autoload.php';
        try
        {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filepath);
            $reader->setReadDataOnly(TRUE);
            $spreadsheet = $reader->load($filepath);
        }
        catch (Throwable $e)
        {
            return FALSE;
        }
        $best = NULL;
        foreach ($spreadsheet->getAllSheets() as $sheet)
        {
            $grid = $sheet->toArray(NULL, TRUE, TRUE, TRUE);
            if ($this->find_header($grid)) { $best = $grid; break; }
            if ($best === NULL && count($grid) > 1) $best = $grid;
        }
        $spreadsheet->disconnectWorksheets();
        return $best === NULL ? FALSE : $best;
    }

    /**
     * Locate the header row and build a column-index => field map.
     * SF1 uses a two-row header: group labels on top, sub-labels below.
     * @return array|null [header_row_index, map]
     */
    private function find_header(array $grid)
    {
        $limit = min(count($grid), 30);
        for ($r = 0; $r < $limit; $r++)
        {
            $cells = isset($grid[$r]) ? $grid[$r] : array();
            $norm = array();
            $has_lrn = FALSE;
            foreach ($cells as $c => $value)
            {
                $norm[$c] = $this->normalize_header($value);
                if ($norm[$c] === 'lrn' || $norm[$c] === 'learnerreferencenumber') $has_lrn = TRUE;
            }
            if ( ! $has_lrn) continue;
            $map = $this->column_map($grid, $r);
            return array($r, $map);
        }
        return NULL;
    }

    /** Map each column index to a canonical field using header + sub-header rows. */
    private function column_map(array $grid, $header_row)
    {
        $map = array();
        $rows = array($header_row, $header_row + 1);
        $max = 0;
        foreach ($rows as $r) if (isset($grid[$r])) $max = max($max, count($grid[$r]));
        $aliases = array();
        foreach (self::column_aliases() as $field => $labels) foreach ($labels as $label) $aliases[$label] = $field;
        for ($c = 0; $c <= $max + 2; $c++)
        {
            foreach ($rows as $r)
            {
                $label = isset($grid[$r][$c]) ? $this->normalize_header($grid[$r][$c]) : '';
                if ($label !== '' && isset($aliases[$label]) && ! in_array($aliases[$label], $map, TRUE))
                {
                    $map[$c] = $aliases[$label];
                    break;
                }
            }
        }
        return $map;
    }

    private function normalize_header($value)
    {
        $value = trim((string) $value);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return preg_replace('/[^a-z0-9]+/', '', $value);
    }

    // ---------------------------------------------------------------
    // Header-block metadata (grade / section / school year hints)
    // ---------------------------------------------------------------

    private function detect_meta(array $grid, $header_row)
    {
        $meta = array('grade' => NULL, 'section' => NULL, 'school_year' => NULL);
        for ($r = 0; $r < $header_row; $r++)
        {
            $cells = isset($grid[$r]) ? $grid[$r] : array();
            foreach ($cells as $c => $value)
            {
                $label = $this->normalize_header($value);
                $next = $this->next_cell($cells, $c);
                if (in_array($label, array('gradelevel', 'grade'), TRUE) && $next !== '')
                {
                    $meta['grade'] = $this->canonical_grade($next);
                }
                if (in_array($label, array('section', 'sectionname'), TRUE) && $next !== '')
                {
                    $meta['section'] = substr(trim($next), 0, 60);
                }
                if (in_array($label, array('schoolyear', 'sy'), TRUE) && $next !== '')
                {
                    $meta['school_year'] = substr(trim($next), 0, 20);
                }
                if ($meta['grade'] === NULL)
                {
                    $g = $this->canonical_grade($value);
                    if ($g !== NULL) $meta['grade'] = $g;
                }
            }
        }
        return $meta;
    }

    private function next_cell(array $cells, $c)
    {
        for ($i = $c + 1; $i < count($cells) + 8; $i++)
        {
            if (isset($cells[$i]) && trim((string) $cells[$i]) !== '') return (string) $cells[$i];
        }
        return '';
    }

    private function canonical_grade($value)
    {
        $value = trim((string) $value);
        if (preg_match('/kindergarten|^kinder|^k$/i', $value)) return 'Kindergarten';
        if (preg_match('/(?:grade|g|gr)?\s*(\d{1,2})\s*$/i', $value, $m))
        {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 12) return 'Grade '.$n;
        }
        return NULL;
    }

    // ---------------------------------------------------------------
    // Row normalization
    // ---------------------------------------------------------------

    private function blank(array $row)
    {
        foreach ($row as $value) if (trim((string) $value) !== '') return FALSE;
        return TRUE;
    }

    private function normalize_row(array $raw, array $map, $row_number)
    {
        $get = function ($field) use ($raw, $map) {
            foreach ($map as $c => $f) if ($f === $field) return isset($raw[$c]) ? trim((string) $raw[$c]) : '';
            return '';
        };
        $fields = array_fill_keys(array(
            'lrn', 'last_name', 'first_name', 'middle_name', 'extension_name', 'sex', 'date_of_birth',
            'mother_tongue', 'ip_ethnicity', 'religion', 'address', 'barangay', 'municipality', 'province',
            'father_name', 'mother_name', 'guardian_name', 'guardian_relationship', 'contact_number', 'remarks',
        ), '');
        foreach ($fields as $field => $v) $fields[$field] = $get($field);

        if ($fields['last_name'] === '' && $fields['first_name'] === '' && $get('full_name') !== '')
        {
            $parts = $this->split_full_name($get('full_name'));
            $fields['last_name'] = $parts['last_name'];
            $fields['first_name'] = $parts['first_name'];
            $fields['middle_name'] = $parts['middle_name'];
            if ($fields['extension_name'] === '') $fields['extension_name'] = $parts['extension_name'];
        }
        // Rows that repeat header labels (a second header block inside the data) are skipped.
        if (in_array($this->normalize_header($fields['last_name']), array('lastname', 'name'), TRUE)
            || $this->normalize_header($fields['lrn']) === 'lrn')
        {
            return NULL;
        }

        $errors = array();
        $fields['lrn'] = preg_replace('/\D+/', '', $fields['lrn']);
        if ($fields['lrn'] !== '' && strlen($fields['lrn']) !== 12)
        {
            $errors[] = 'LRN must be exactly 12 digits.';
        }
        if ($fields['last_name'] === '') $errors[] = 'Last name is required.';
        if ($fields['first_name'] === '') $errors[] = 'First name is required.';
        $fields['sex'] = $this->normalize_sex($fields['sex']);
        if ($fields['sex'] === NULL) $errors[] = 'Sex must be M or F.';
        $dob = $this->parse_date($fields['date_of_birth']);
        if ($dob === NULL)
        {
            $errors[] = 'Birth date is missing or unrecognized (use mm/dd/yyyy).';
        }
        elseif ($dob > date('Y-m-d'))
        {
            $errors[] = 'Birth date is in the future.';
        }
        $fields['date_of_birth'] = $dob;
        foreach (array('last_name' => 80, 'first_name' => 80, 'middle_name' => 80, 'extension_name' => 20,
            'mother_tongue' => 120, 'ip_ethnicity' => 120, 'religion' => 120, 'address' => 255,
            'barangay' => 120, 'municipality' => 120, 'province' => 120, 'father_name' => 160,
            'mother_name' => 160, 'guardian_name' => 160, 'guardian_relationship' => 60, 'contact_number' => 30) as $field => $max)
        {
            $len = function_exists('mb_strlen') ? mb_strlen($fields[$field], 'UTF-8') : strlen($fields[$field]);
            if ($len > $max) $fields[$field] = function_exists('mb_substr') ? mb_substr($fields[$field], 0, $max, 'UTF-8') : substr($fields[$field], 0, $max);
        }
        $fields['contact_number'] = substr(preg_replace('/[^\d+]+/', '', $fields['contact_number']), 0, 30);
        $rel = $fields['guardian_relationship'];
        foreach (array('father' => 'Father', 'mother' => 'Mother', 'guardian' => 'Guardian', 'parent' => 'Parent') as $needle => $canon)
            if (stripos($rel, $needle) !== FALSE) { $fields['guardian_relationship'] = $canon; break; }

        return array(
            'source_row' => $row_number,
            'fields' => $fields,
            'errors' => $errors,
            'valid' => ! $errors,
        );
    }

    /** "DELA CRUZ, JUAN MIGUEL JR." -> name parts. */
    private function split_full_name($value)
    {
        $parts = array('last_name' => '', 'first_name' => '', 'middle_name' => '', 'extension_name' => '');
        $value = trim((string) $value);
        if ($value === '') return $parts;
        $halves = array_map('trim', explode(',', $value, 2));
        $parts['last_name'] = $halves[0];
        if ( ! isset($halves[1]) || $halves[1] === '') { $parts['first_name'] = $halves[0]; $parts['last_name'] = ''; return $parts; }
        $tokens = preg_split('/\s+/', $halves[1]);
        $last = strtoupper(rtrim(end($tokens), '.'));
        if (in_array($last, array('JR', 'SR', 'II', 'III', 'IV', 'V'), TRUE))
        {
            $parts['extension_name'] = array_pop($tokens);
        }
        if (count($tokens) > 1)
        {
            $parts['middle_name'] = array_pop($tokens);
        }
        $parts['first_name'] = implode(' ', $tokens);
        return $parts;
    }

    private function normalize_sex($value)
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') return NULL;
        if (in_array($value, array('M', 'MALE', 'LALAKI', 'BOY'), TRUE)) return 'Male';
        if (in_array($value, array('F', 'FEMALE', 'BABAE', 'GIRL'), TRUE)) return 'Female';
        return NULL;
    }

    public function parse_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') return NULL;
        if (is_numeric($value) && $value > 10000 && $value < 80000)
        {
            try
            {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                $out = $date->format('Y-m-d');
                return $this->valid_date($out) ? $out : NULL;
            }
            catch (Throwable $e) { return NULL; }
        }
        foreach (array('Y-m-d', 'm/d/Y', 'm/d/y', 'm-d-Y', 'm-d-y', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'F j, Y', 'j F Y', 'M j, Y', 'j M Y') as $format)
        {
            $date = DateTime::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value)
            {
                $out = $date->format('Y-m-d');
                return $this->valid_date($out) ? $out : NULL;
            }
        }
        return NULL;
    }

    private function valid_date($ymd)
    {
        $year = (int) substr($ymd, 0, 4);
        return $year >= 1900 && $year <= (int) date('Y');
    }

    private function fold($value)
    {
        $value = trim((string) $value);
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    private function error($message)
    {
        return array('ok' => FALSE, 'error' => $message, 'meta' => array(), 'rows' => array());
    }
}
