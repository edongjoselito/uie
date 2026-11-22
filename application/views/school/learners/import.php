<?php
$status_badge = function ($status) {
    $map = array(
        'create' => array('New Learner', 'success'),
        'enroll' => array('Add Enrollment', 'primary'),
        'update' => array('Will Update', 'warning'),
        'error'  => array('Needs Fixing', 'danger'),
    );
    $entry = isset($map[$status]) ? $map[$status] : array($status, 'secondary');
    return '<span class="badge text-bg-'.$entry[1].'">'.e($entry[0]).'</span>';
};
?>
<div class="learner-workspace">
    <a class="learner-back" href="<?php echo site_url('learners'); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to learner profiles</a>
    <header class="learner-page-heading">
        <div><p class="learner-eyebrow">LEARNERS / PROFILE</p><h1>Import SF 1 · School Register</h1><p>Upload the class register to create learner profiles and enrollments in one pass.</p></div>
        <span class="learner-school-pill"><i class="bi bi-building" aria-hidden="true"></i><span><?php echo e($school->name); ?></span></span>
    </header>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo e($error); ?></div><?php endif; ?>

    <?php if ( ! $preview): ?>
    <div class="learner-entry-layout">
        <div class="learner-entry-sections">
            <section class="learner-entry-card" aria-labelledby="upload-title">
                <div class="learner-section-heading"><span class="learner-step">01</span><div><h2 id="upload-title">Upload the Register</h2><p>The official SF 1 workbook is detected by its column headers. Both the two-row merged header and a flat header row work.</p></div><i class="bi bi-file-earmark-spreadsheet learner-section-icon" aria-hidden="true"></i></div>
                <div class="learner-section-body">
                    <?php echo form_open_multipart('learners/import', array('class' => 'learner-entry-form')); ?>
                    <input type="hidden" name="import_stage" value="preview">
                    <div class="row g-4">
                        <div class="col-md-4"><label for="school-year" class="form-label">School Year <span class="learner-required" aria-hidden="true">*</span></label><select id="school-year" name="school_year_id" class="form-select" required>
                            <?php foreach ($years as $sy): if ($sy->status !== 'open') continue; ?><option value="<?php echo (int) $sy->id; ?>" <?php echo (int) $year_id === (int) $sy->id ? 'selected' : ''; ?>><?php echo e($sy->year_label); ?></option><?php endforeach; ?>
                        </select><div class="form-text">Open years only.</div></div>
                        <div class="col-md-4"><label for="grade-level" class="form-label">Grade Level <span class="learner-required" aria-hidden="true">*</span></label><select id="grade-level" name="grade_level" class="form-select" required><option value="">Select grade level</option><?php foreach ($grades as $grade): ?><option <?php echo set_select('grade_level', $grade); ?>><?php echo e($grade); ?></option><?php endforeach; ?></select><div class="form-text">Applied to every imported row.</div></div>
                        <div class="col-md-4"><label for="section" class="form-label">Section <span class="learner-optional">Optional</span></label><input id="section" name="section" class="form-control" maxlength="60" value="<?php echo e(set_value('section')); ?>" placeholder="e.g., Rizal"></div>
                        <div class="col-md-8"><label for="sf1-file" class="form-label">SF 1 Spreadsheet <span class="learner-required" aria-hidden="true">*</span></label><input id="sf1-file" name="sf1_file" type="file" class="form-control" accept=".xlsx,.xls,.csv" required><div class="form-text">.xlsx, .xls, or .csv · up to 8 MB · up to <?php echo (int) Sf1_import::MAX_ROWS; ?> rows.</div></div>
                        <div class="col-md-4 d-flex align-items-end"><a class="btn btn-outline-secondary w-100" href="<?php echo site_url('learners/sf1_template'); ?>"><i class="bi bi-download" aria-hidden="true"></i> Download Template</a></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="tag_iped" id="tag-iped" value="1" <?php echo set_checkbox('tag_iped', '1'); ?>><label class="form-check-label" for="tag-iped">Tag learners with an <strong>IP (Ethnic Group)</strong> entry as <strong>IPEd</strong> for this enrollment</label><div class="form-text">Program-specific details can be completed afterwards by editing the enrollment.</div></div></div>
                    </div>
                    <div class="learner-form-actions"><span><i class="bi bi-shield-lock" aria-hidden="true"></i> Review happens before anything is saved.</span><div><a class="btn learner-cancel" href="<?php echo site_url('learners'); ?>">Cancel</a><button class="btn btn-primary learner-save" type="submit"><i class="bi bi-eye" aria-hidden="true"></i> Review file</button></div></div>
                    <?php echo form_close(); ?>
                </div>
            </section>
        </div>
        <aside class="learner-summary" aria-labelledby="columns-title">
            <div class="learner-summary-heading"><span class="learner-summary-icon"><i class="bi bi-layout-three-columns" aria-hidden="true"></i></span><h2 id="columns-title">Expected Columns</h2><p>Kept aligned with SF 1.</p></div>
            <ul class="sf1-column-list">
                <li>LRN</li><li>Last · First · Middle · Extension Name</li><li>Sex · Birth Date</li>
                <li>Mother Tongue · IP (Ethnic Group) · Religion</li><li>Address: Street/Sitio · Barangay · Municipality · Province</li>
                <li>Father · Mother (Maiden) · Guardian · Relationship</li><li>Contact Number · Remarks</li>
            </ul>
            <div class="learner-summary-note"><i class="bi bi-shield-check" aria-hidden="true"></i><p>Learners are matched by LRN, or by exact name + birth date within your school. Records registered at another school are skipped and reported.</p></div>
        </aside>
    </div>

    <?php else:
        $importable = 0; $problems = 0;
        foreach ($preview['rows'] as $row) { if ( ! empty($row['valid'])) $importable++; else $problems++; }
    ?>
    <section class="learner-entry-card" aria-labelledby="preview-title">
        <div class="learner-section-heading"><span class="learner-step">02</span><div><h2 id="preview-title">Review Before Import</h2><p><?php echo e($preview['filename']); ?> · header found on row <?php echo (int) $preview['meta']['header_row']; ?> · <?php echo number_format($importable); ?> row<?php echo $importable === 1 ? '' : 's'; ?> ready<?php echo $problems ? ', '.$problems.' need attention' : ''; ?>.</p></div><i class="bi bi-clipboard-data learner-section-icon" aria-hidden="true"></i></div>
        <div class="learner-section-body">
            <div class="sf1-preview-meta">
                <span><i class="bi bi-calendar3" aria-hidden="true"></i> Enrolling into <strong>SY <?php echo e($preview['year_label']); ?></strong></span>
                <span><i class="bi bi-mortarboard" aria-hidden="true"></i> <strong><?php echo e($preview['grade']); ?></strong><?php echo $preview['section'] !== '' ? ' · '.e($preview['section']) : ' · No section'; ?></span>
                <?php if ($preview['tag_iped']): ?><span><i class="bi bi-tree" aria-hidden="true"></i> IP learners tagged <strong>IPEd</strong></span><?php endif; ?>
                <?php if ( ! empty($preview['meta']['grade']) && $preview['meta']['grade'] !== $preview['grade']): ?><span class="sf1-meta-warning"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> The file header says <?php echo e($preview['meta']['grade']); ?> — check your selection.</span><?php endif; ?>
                <?php if ( ! empty($preview['meta']['section']) && $preview['section'] === ''): ?><span><i class="bi bi-info-circle" aria-hidden="true"></i> File section: <?php echo e($preview['meta']['section']); ?> (not applied — set it on the upload form to use it)</span><?php endif; ?>
            </div>
            <?php echo form_open('learners/import'); ?>
            <input type="hidden" name="import_stage" value="commit">
            <input type="hidden" name="import_token" value="<?php echo e($preview['token']); ?>">
            <div class="directory-table-scroll sf1-preview-scroll" role="region" aria-label="SF 1 Import Preview" tabindex="0">
                <table class="table directory-table sf1-preview-table">
                    <thead><tr>
                        <th scope="col"><input type="checkbox" id="sf1-select-all" class="form-check-input" checked aria-label="Select all importable rows"></th>
                        <th scope="col">Row</th><th scope="col">LRN</th><th scope="col">Learner Name</th><th scope="col">Sex</th><th scope="col">Birth Date</th>
                        <th scope="col">Mother Tongue</th><th scope="col">IP (Ethnic Group)</th><th scope="col">Religion</th>
                        <th scope="col">Address</th><th scope="col">Parents / Guardian</th><th scope="col">Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($preview['rows'] as $i => $row): $f = $row['fields'];
                        $address = implode(', ', array_filter(array($f['address'], $f['barangay'], $f['municipality'], $f['province'])));
                        $parents = array();
                        if ($f['father_name'] !== '') $parents[] = 'F: '.$f['father_name'];
                        if ($f['mother_name'] !== '') $parents[] = 'M: '.$f['mother_name'];
                        if ($f['guardian_name'] !== '') $parents[] = 'G: '.$f['guardian_name'].($f['guardian_relationship'] !== '' ? ' ('.$f['guardian_relationship'].')' : '');
                    ?><tr class="<?php echo $row['valid'] ? '' : 'sf1-row-invalid'; ?>">
                        <td><?php if ($row['valid']): ?><input type="checkbox" name="rows[]" value="<?php echo (int) $i; ?>" class="form-check-input sf1-row-check" checked aria-label="<?php echo e('Import '.$f['first_name'].' '.$f['last_name']); ?>"><?php endif; ?></td>
                        <td class="directory-row-number"><?php echo (int) $row['source_row']; ?></td>
                        <td><span class="directory-lrn <?php echo $f['lrn'] !== '' ? '' : 'is-missing'; ?>"><?php echo e($f['lrn'] !== '' ? $f['lrn'] : '—'); ?></span></td>
                        <td><strong><?php echo e($f['last_name'].', '.$f['first_name']); ?></strong><?php if ($f['middle_name'] !== '' || $f['extension_name'] !== ''): ?><br><span class="sf1-subtle"><?php echo e(trim($f['middle_name'].' '.$f['extension_name'])); ?></span><?php endif; ?></td>
                        <td><?php echo e($f['sex'] !== NULL ? substr($f['sex'], 0, 1) : '—'); ?></td>
                        <td><?php echo e($f['date_of_birth'] !== NULL ? $f['date_of_birth'] : '—'); ?></td>
                        <td><?php echo e($f['mother_tongue'] !== '' ? $f['mother_tongue'] : '—'); ?></td>
                        <td><?php echo e($f['ip_ethnicity'] !== '' ? $f['ip_ethnicity'] : '—'); ?></td>
                        <td><?php echo e($f['religion'] !== '' ? $f['religion'] : '—'); ?></td>
                        <td class="sf1-cell-wide"><?php echo e($address !== '' ? $address : '—'); ?></td>
                        <td class="sf1-cell-wide"><?php foreach ($parents as $p): ?><span class="sf1-parent-line"><?php echo e($p); ?></span><?php endforeach; if ( ! $parents && $f['contact_number'] === '') echo '—'; if ($f['contact_number'] !== ''): ?><span class="sf1-subtle"><?php echo e($f['contact_number']); ?></span><?php endif; ?></td>
                        <td><?php if ($row['valid']): ?><?php echo $status_badge($row['status']); ?><div class="sf1-status-note"><?php echo e($row['status_message']); ?></div><?php else: ?><?php echo $status_badge('error'); ?><div class="sf1-status-note"><?php echo e(implode(' ', array_merge($row['errors'], isset($row['status_message']) && $row['status_message'] !== '' ? array($row['status_message']) : array()))); ?></div><?php endif; ?></td>
                    </tr><?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="learner-form-actions"><span><strong id="sf1-selected-count"><?php echo number_format($importable); ?></strong> of <?php echo number_format(count($preview['rows'])); ?> rows selected.</span><div><a class="btn learner-cancel" href="<?php echo site_url('learners/import'); ?>">Upload a different file</a><button class="btn btn-primary learner-save" type="submit" <?php echo $importable ? '' : 'disabled'; ?>><i class="bi bi-cloud-upload" aria-hidden="true"></i> Import selected rows</button></div></div>
            <?php echo form_close(); ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php if ($preview): ?>
<script>
(function () {
    var all = document.getElementById('sf1-select-all');
    var boxes = document.querySelectorAll('.sf1-row-check');
    var counter = document.getElementById('sf1-selected-count');
    function recount() {
        var n = 0;
        boxes.forEach(function (b) { if (b.checked) n++; });
        counter.textContent = n;
        all.checked = n === boxes.length;
        all.indeterminate = n > 0 && n < boxes.length;
    }
    all.addEventListener('change', function () {
        boxes.forEach(function (b) { b.checked = all.checked; });
        recount();
    });
    boxes.forEach(function (b) { b.addEventListener('change', recount); });
    recount();
}());
</script>
<?php endif; ?>
