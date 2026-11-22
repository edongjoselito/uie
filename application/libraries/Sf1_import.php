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
            'full_name' => array('name', 'learnername', 'learnersname', 'nameoflearner', 'fullname', 'namelastnamefirstnamemiddlename'),
            'sex' => array('sex', 'sexmf', 'gender', 'mf', 'mfemale', 'kasarian'),
            'date_of_birth' => array('birthdate', 'birthdatemmddyyyy', 'dateofbirth', 'dob', 'birthday', 'birthdatemmddyy', 'petsangkapanganakan', 'kapanganakan'),
            'place_of_birth' => array('birthplace', 'birthplaceprovince', 'placeofbirth', 'placeofbirthprovince'),
            'mother_tongue' => array('mothertongue', 'mt', 'mothertonguelanguage', 'wikangginagamitsabahay'),
            'ip_ethnicity' => array('ip', 'ipethnicgroup', 'ipethnicity', 'ethnicgroup', 'indigenouspeoples', 'ipeethnicgroup', 'ipgroup', 'ethnicity'),
            'religion' => array('religion', 'religiousaffiliation', 'relihiyon'),
            'address' => array('housenostreetsitio', 'housenostreetsitiopurok', 'housestreetsitiopurok', 'housestreet', 'housenostreet', 'housenumberstreet', 'street', 'sitio', 'address', 'addressstreet', 'housenostreetbarangay'),
            'barangay' => array('barangay', 'brgy', 'baranggay'),
            'municipality' => array('municipalitycity', 'municipality', 'citymunicipality', 'towncity', 'town', 'city', 'mun'),
            'province' => array('province', 'lalawigan'),
            'father_name' => array('fathersname', 'fathername', 'father', 'fathersnamelastfirstmiddle', 'fathersnamelastnamefirstnamemiddlename', 'fatherfullname'),
            'mother_name' => array('mothersmaidenname', 'mothersname', 'mothername', 'mothermaidenname', 'maidenname', 'mother', 'mothersmaidennameklastfirstmiddle', 'mothersmaidennamelastnamefirstnamemiddlename', 'motherfullname'),
            'guardian_name' => array('guardianname', 'guardiansname', 'nameofguardian', 'guardian', 'guardiansfullname', 'guardiannameifnotparents', 'guardianifnotparent'),
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
        $autoload = FCPATH.'vendor/autoload.php';
        if ( ! is_file($autoload))
        {
            return $this->error('Spreadsheet import is unavailable because the application dependencies are missing.');
        }
        require_once $autoload;
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
        list($header_row, $map, $grouped) = $found;
        if ( ! in_array('full_name', $map, TRUE)
            && ( ! in_array('last_name', $map, TRUE) || ! in_array('first_name', $map, TRUE)))
        {
            return $this->error('The learner name columns could not be identified. Keep the SF 1 Name header and enter names as Last Name, First Name, Middle Name.');
        }
        if ( ! in_array('sex', $map, TRUE) || ! in_array('date_of_birth', $map, TRUE))
        {
            return $this->error('The Sex and Birth Date columns are required. Keep the standard SF 1 column headers.');
        }

        $meta = $this->detect_meta($grid, $header_row);
        $meta['header_row'] = $header_row + 1; // 1-based for display
        $meta['columns'] = $map;

        $rows = array();
        $seen = array();
        for ($r = $header_row + ($grouped ? 2 : 1); $r < count($grid); $r++)
        {
            $raw = $grid[$r];
            if (stripos(trim((string) ($raw[0] ?? '')), 'List and Code of Indicators under REMARKS') === 0) break;
            if ($this->blank($raw)) continue;
            $row = $this->normalize_row($raw, $map, $r + 1);
            if ($row === NULL) continue; // repeated header text
            if (count($rows) === self::MAX_ROWS)
            {
                return $this->error('The file exceeds the '.self::MAX_ROWS.' row limit. Split the register into smaller files.');
            }
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
        return array('ok' => TRUE, 'error' => NULL, 'meta' => $meta, 'rows' => $rows);
    }

    // ---------------------------------------------------------------
    // Workbook reading
    // ---------------------------------------------------------------

    private function read_grid($filepath)
    {
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
            $grid = $sheet->toArray(NULL, TRUE, TRUE, FALSE);
            if ($this->find_header($grid)) { $best = $grid; break; }
            if ($best === NULL && count($grid) > 1) $best = $grid;
        }
        $spreadsheet->disconnectWorksheets();
        return $best === NULL ? FALSE : $best;
    }

    /**
     * Locate the header row and build a column-index => field map.
     * SF1 uses a two-row header: group labels on top, sub-labels below.
     * @return array|null [header_row_index, map, has_subheaders]
     */
    private function find_header(array $grid)
    {
        $limit = min(count($grid), 30);
        $lrn_headers = self::column_aliases()['lrn'];
        for ($r = 0; $r < $limit; $r++)
        {
            $cells = isset($grid[$r]) ? $grid[$r] : array();
            foreach ($cells as $value)
            {
                if (in_array($this->normalize_header($value), $lrn_headers, TRUE))
                {
                    list($map, $grouped) = $this->column_map($grid, $r);
                    return array($r, $map, $grouped);
                }
            }
        }
        return NULL;
    }

    /** Map each column index to a canonical field using header + sub-header rows. */
    private function column_map(array $grid, $header_row)
    {
        $map = array();
        $aliases = array();
        foreach (self::column_aliases() as $field => $labels) foreach ($labels as $label) $aliases[$label] = $field;
        $headers = array_map(array($this, 'normalize_header'), $grid[$header_row]);
        $below = $grid[$header_row + 1] ?? array();
        $subheaders = array_map(array($this, 'normalize_header'), $below);
        $grouped = FALSE;
        $group_fields = array('name' => 'last_name', 'address' => 'address', 'parents' => 'father_name', 'parentsguardian' => 'father_name');
        foreach ($headers as $c => $header)
        {
            if (isset($group_fields[$header]) && ($aliases[$subheaders[$c] ?? ''] ?? NULL) === $group_fields[$header])
            {
                $grouped = TRUE;
                break;
            }
        }
        foreach ($headers as $c => $header)
        {
            $field = $aliases[$header] ?? NULL;
            $subfield = $aliases[$subheaders[$c] ?? ''] ?? NULL;
            if ($grouped && ($field === NULL || ($header === 'name' && $subfield === 'last_name')))
            {
                $field = $subfield;
            }
            if ($field !== NULL && ! in_array($field, $map, TRUE))
            {
                $map[$c] = $field;
            }
        }
        return array($map, $grouped);
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
                if (preg_match('/\bGrade Level:\s*(Kindergarten|(?:Grade\s*)?\d{1,2})\b/i', (string) $value, $match))
                {
                    $meta['grade'] = $this->canonical_grade($match[1]);
                }
                elseif ($meta['grade'] === NULL && preg_match('/^(?:Grade\s*\d{1,2}|Kinder(?:garten)?)$/i', trim((string) $value)))
                {
                    $meta['grade'] = $this->canonical_grade($value);
                }
                if (preg_match('/\bSection:\s*(.+)$/i', (string) $value, $match))
                {
                    $meta['section'] = substr(trim($match[1]), 0, 60);
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
            'place_of_birth', 'mother_tongue', 'ip_ethnicity', 'religion', 'address', 'barangay', 'municipality', 'province',
            'father_name', 'mother_name', 'guardian_name', 'guardian_relationship', 'contact_number', 'remarks',
        ), '');
        foreach ($fields as $field => $v) $fields[$field] = $get($field);

        $full_name = $get('full_name');
        if (in_array($this->normalize_header($fields['lrn']), self::column_aliases()['lrn'], TRUE)
            || in_array($this->normalize_header($fields['last_name']), self::column_aliases()['last_name'], TRUE)
            || ($full_name === '' && $this->blank($fields)))
        {
            return NULL;
        }

        $errors = array();
        if ($fields['last_name'] === '' && $fields['first_name'] === '' && $full_name !== '')
        {
            $parts = array_map('trim', explode(',', $full_name));
            if (count($parts) >= 2 && count($parts) <= 3)
            {
                list($fields['last_name'], $fields['first_name']) = $parts;
                $fields['middle_name'] = $parts[2] ?? '';
            }
            else
            {
                $errors[] = 'Enter the name as Last Name, First Name, Middle Name.';
            }
        }
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
            'place_of_birth' => 200, 'mother_tongue' => 120, 'ip_ethnicity' => 120, 'religion' => 120, 'address' => 255,
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
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $parts)
            && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[3]))
        {
            $out = sprintf('%04d-%02d-%02d', $parts[3], $parts[1], $parts[2]);
            return $this->valid_date($out) ? $out : NULL;
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
