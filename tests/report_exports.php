<?php
if (PHP_SAPI !== 'cli') exit(1);
define('FCPATH', dirname(__DIR__).'/');
define('BASEPATH', FCPATH.'system/');
require FCPATH.'application/libraries/Report_export.php';
function check($ok, $message) { if ( ! $ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
$exporter = new Report_export();
$meta = array('title' => 'SNED Learner Report', 'school' => 'Peña & Sons <School>', 'year' => '2026-2027', 'grade' => 'Grade 5', 'generated' => 'Nov 22, 2026 PHT', 'note' => 'All matching records.');
$columns = array('#', 'Learner Name', 'LRN', 'Sex', 'Date of Birth', 'Grade Level', 'Section', 'Program Tags', 'Enrollment Status');
$rows = array();
for ($i = 1; $i <= 65; $i++) $rows[] = array($i, $i === 1 ? '=HYPERLINK("https://example.invalid")' : 'PEÑA, JOSÉ '.$i, '001234567890', 'Female', '2015-01-01', 'Grade 5', 'A & B', 'SNED, IPEd', 'Enrolled');
foreach (array('xlsx', 'docx', 'pdf') as $format)
{
    $bytes = $exporter->create($format, $meta, $columns, $rows);
    $file = tempnam(sys_get_temp_dir(), 'uie-export-test-');
    try
    {
        file_put_contents($file, $bytes);
        if ($format === 'pdf')
        {
            check(strncmp($bytes, '%PDF-', 5) === 0 && strpos($bytes, '%%EOF') !== FALSE, 'PDF is a complete document');
            check(preg_match_all('~/Type\s*/Page\b~', $bytes) > 1, 'Long PDF spans multiple pages');
        }
        else
        {
            $zip = new ZipArchive();
            check($zip->open($file) === TRUE, strtoupper($format).' is a genuine zipped Office document');
            if ($format === 'xlsx')
            {
                $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
                $strings = $zip->getFromName('xl/sharedStrings.xml');
                check(strpos($sheet, 'r="A72"') !== FALSE, 'Excel includes records beyond page 25');
                check(strpos($strings, '001234567890') !== FALSE && strpos($sheet, '<f>') === FALSE, 'Excel preserves LRN leading zeros and disables formula injection');
            }
            else
            {
                $xml = $zip->getFromName('word/document.xml');
                check(strpos($xml, 'PEÑA, JOSÉ 65') !== FALSE && strpos($xml, 'A &amp; B') !== FALSE, 'Word preserves all rows, accented names, and XML escaping');
                $doc = new DOMDocument();
                check($doc->loadXML($xml), 'Word document XML is valid');
            }
            $zip->close();
        }
    }
    finally { unlink($file); }
}
foreach (array('xlsx', 'docx', 'pdf') as $format)
    check(strlen($exporter->create($format, $meta, $columns, array())) > 500, strtoupper($format).' supports an empty report');
