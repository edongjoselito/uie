<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Genuine Office Open XML and PDF documents; no public temporary artifacts. */
class Report_export
{
    private $temp_dir;
    public function create($format, array $meta, array $columns, iterable $rows)
    {
        require_once FCPATH.'vendor/autoload.php';
        foreach (array(sys_get_temp_dir(), ini_get('upload_tmp_dir'), '/tmp') as $parent)
        {
            if ( ! $parent || ! is_dir($parent) || ! is_writable($parent)) continue;
            $path = rtrim($parent, '/\\').DIRECTORY_SEPARATOR.'uie-export-'.bin2hex(random_bytes(12));
            if (mkdir($path, 0700)) { $this->temp_dir = $path; break; }
        }
        if ( ! $this->temp_dir) throw new RuntimeException('No writable temporary directory is available for reports.');
        try
        {
            if ($format === 'xlsx') return $this->excel($meta, $columns, $rows);
            if ($format === 'docx') return $this->word($meta, $columns, $rows);
            if ($format === 'pdf') return $this->pdf($meta, $columns, $rows);
            throw new InvalidArgumentException('Unsupported report format.');
        }
        finally
        {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->temp_dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) { if ($file->isDir() && ! $file->isLink()) rmdir($file->getPathname()); else unlink($file->getPathname()); }
            rmdir($this->temp_dir);
            $this->temp_dir = NULL;
        }
    }

    private function excel(array $meta, array $columns, iterable $rows)
    {
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Learner Report');
        $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns));
        $lines = array($meta['title'], $meta['school'], 'School Year: '.$meta['year'].' | Grade: '.$meta['grade'], 'Generated: '.$meta['generated'], $meta['note']);
        foreach ($lines as $i => $line)
        {
            $r = $i + 1;
            $sheet->setCellValueExplicit('A'.$r, $line, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->mergeCells('A'.$r.':'.$last.$r);
        }
        $sheet->getStyle('A1')->getFont()->setBold(TRUE)->setSize(18)->getColor()->setARGB('FF153E63');
        $sheet->getStyle('A5:'.$last.'5')->getAlignment()->setWrapText(TRUE);
        $sheet->getRowDimension(5)->setRowHeight(42);
        $header = 7;
        foreach ($columns as $i => $column) $sheet->setCellValueExplicit([$i + 1, $header], $column, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $r = $header;
        foreach ($rows as $row)
        {
            $r++;
            foreach ($row as $i => $value)
            {
                // Explicit strings prevent formulas and preserve leading zeros in LRNs.
                $sheet->setCellValueExplicit([$i + 1, $r], $value, is_int($value) ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        if ($r === $header) { $r++; $sheet->setCellValue('A'.$r, 'No learners match the selected filters.'); }
        $sheet->getStyle('A7:'.$last.'7')->applyFromArray(array('font' => array('bold' => TRUE, 'color' => array('argb' => 'FFFFFFFF')), 'fill' => array('fillType' => 'solid', 'startColor' => array('argb' => 'FF153E63'))));
        $sheet->getStyle('A7:'.$last.$r)->getAlignment()->setVertical('top')->setWrapText(TRUE);
        foreach ($columns as $i => $column) $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1))->setWidth($column === 'Learner Name' ? 38 : ($column === 'Program Tags' || $column === 'Learner Group' ? 28 : 19));
        $sheet->freezePane('A8');
        $sheet->setAutoFilter('A7:'.$last.$r);
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd(7, 7);
        try { return $this->writer_bytes(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book)); }
        finally { $book->disconnectWorksheets(); }
    }

    private function word(array $meta, array $columns, iterable $rows)
    {
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(TRUE);
        \PhpOffice\PhpWord\Settings::setTempDir($this->temp_dir);
        $document = new \PhpOffice\PhpWord\PhpWord();
        $document->setDefaultFontName('Calibri');
        $document->setDefaultFontSize(9);
        $section = $document->addSection(array('orientation' => 'landscape', 'marginLeft' => 600, 'marginRight' => 600, 'marginTop' => 600, 'marginBottom' => 600));
        $section->addText($meta['title'], array('bold' => TRUE, 'size' => 18, 'color' => '153E63'));
        $section->addText($meta['school'], array('bold' => TRUE, 'size' => 11));
        $section->addText('School Year: '.$meta['year'].' | Grade: '.$meta['grade']);
        $section->addText('Generated: '.$meta['generated']);
        $section->addText($meta['note'], array('size' => 8, 'color' => '64748B'));
        $table = $section->addTable(array('borderSize' => 4, 'borderColor' => 'DBE4EE', 'cellMargin' => 90));
        $table->addRow(NULL, array('tblHeader' => TRUE));
        $width = (int) floor(14500 / count($columns));
        foreach ($columns as $column) $table->addCell($width, array('bgColor' => '153E63'))->addText($column, array('bold' => TRUE, 'color' => 'FFFFFF', 'size' => 8));
        $count = 0;
        foreach ($rows as $row)
        {
            $table->addRow(NULL, array('cantSplit' => TRUE));
            foreach ($row as $value) $table->addCell($width, array('bgColor' => $count % 2 ? 'F5F8FC' : 'FFFFFF'))->addText((string) $value, array('size' => 8));
            $count++;
        }
        if ( ! $count) $section->addText('No learners match the selected filters.');
        $section->addFooter()->addPreserveText('Page {PAGE} of {NUMPAGES}', array('size' => 8), array('alignment' => 'right'));
        return $this->writer_bytes(\PhpOffice\PhpWord\IOFactory::createWriter($document, 'Word2007'));
    }

    private function pdf(array $meta, array $columns, iterable $rows)
    {
        $escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>@page{margin:28px}body{font-family:DejaVu Sans,sans-serif;font-size:8px;color:#294763}h1{font-size:20px}h2{font-size:12px}.note{font-size:8px;color:#64748b}table{border-collapse:collapse;width:100%;table-layout:fixed}thead{display:table-header-group}th{background:#153e63;color:#fff;text-align:left}th,td{border:1px solid #dce4ee;padding:6px;word-wrap:break-word;vertical-align:top}tr{page-break-inside:avoid}tbody tr:nth-child(even){background:#f5f8fc}</style></head><body>';
        $html .= '<h1>'.$escape($meta['title']).'</h1><h2>'.$escape($meta['school']).'</h2><p>School Year: '.$escape($meta['year']).' | Grade: '.$escape($meta['grade']).'</p><p>Generated: '.$escape($meta['generated']).'</p><p class="note">'.$escape($meta['note']).'</p><table><thead><tr>';
        foreach ($columns as $column) $html .= '<th>'.$escape($column).'</th>';
        $html .= '</tr></thead><tbody>';
        $count = 0;
        foreach ($rows as $row)
        {
            $html .= '<tr>';
            foreach ($row as $value) $html .= '<td>'.$escape($value).'</td>';
            $html .= '</tr>'; $count++;
        }
        if ( ! $count) $html .= '<tr><td colspan="'.count($columns).'">No learners match the selected filters.</td></tr>';
        $html .= '</tbody></table></body></html>';
        $options = new \Dompdf\Options();
        $options->set('tempDir', $this->temp_dir);
        $options->set('fontCache', $this->temp_dir);
        $options->set('isRemoteEnabled', FALSE);
        $options->set('isPhpEnabled', FALSE);
        $options->set('isJavascriptEnabled', FALSE);
        $options->set('chroot', FCPATH.'vendor/dompdf');
        $pdf = new \Dompdf\Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        $pdf->getCanvas()->page_text(740, 570, 'Page {PAGE_NUM} of {PAGE_COUNT}', NULL, 8, array(.4, .4, .4));
        return $pdf->output();
    }

    private function writer_bytes($writer)
    {
        $file = tempnam($this->temp_dir, 'uie-report-');
        if ($file === FALSE) throw new RuntimeException('Unable to create report file.');
        try
        {
            $writer->save($file);
            $bytes = file_get_contents($file);
            if ($bytes === FALSE) throw new RuntimeException('Unable to read report file.');
            return $bytes;
        }
        finally { if (is_file($file)) unlink($file); }
    }
}
