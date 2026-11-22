<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2"></i>School Years</h4>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-uie table-hover mb-0">
                    <thead><tr><th>School Year</th><th class="text-center">Start</th><th class="text-center">End</th><th class="text-center">Active</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $sy): ?>
                    <tr <?php echo $sy->is_active ? 'class="table-success"' : ''; ?>>
                        <td class="fw-semibold">SY <?php echo e($sy->year_label); ?></td>
                        <td class="text-center"><?php echo e($sy->start_year); ?></td>
                        <td class="text-center"><?php echo e($sy->end_year); ?></td>
                        <td class="text-center"><?php echo $sy->is_active ? '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>' : ''; ?></td>
                        <td><?php echo status_badge($sy->status); ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ( ! $sy->is_active && $this->access_control->can('school_years.manage')): ?>
                            <?php echo form_open('superadmin/school_years/set_active/'.$sy->id, array('class' => 'd-inline', 'data-confirm' => 'Set SY '.$sy->year_label.' as the ACTIVE school year?')); ?>
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2-circle"></i> Set Active</button>
                            <?php echo form_close(); ?>
                            <?php endif; ?>
                            <?php if ($this->access_control->can('school_years.manage')): ?>
                            <?php echo form_open('superadmin/school_years/toggle_status/'.$sy->id, array('class' => 'd-inline', 'data-confirm' => ($sy->status === 'open' ? 'Close' : 'Reopen').' school year '.$sy->year_label.'?')); ?>
                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-<?php echo $sy->status === 'open' ? 'lock' : 'unlock'; ?>"></i> <?php echo $sy->status === 'open' ? 'Close' : 'Reopen'; ?></button>
                            <?php echo form_close(); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <?php if ($this->access_control->can('school_years.manage')): ?>
        <div class="card">
            <div class="card-header bg-white fw-semibold">Add School Year</div>
            <div class="card-body">
                <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>
                <?php echo form_open('superadmin/school_years/create'); ?>
                <label class="form-label required-label">School Year Label</label>
                <input type="text" name="year_label" class="form-control mb-2" placeholder="YYYY-YYYY e.g. 2027-2028" pattern="\d{4}-\d{4}" required>
                <small class="text-muted d-block mb-3">Consecutive years only (end year = start year + 1).</small>
                <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Add School Year</button>
                <?php echo form_close(); ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="card mt-3">
            <div class="card-body small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                The <b>active school year</b> is used as the default for dashboards, new enrollments, and submissions.
                <b>Closing</b> a school year marks it read-only for historical reporting.
            </div>
        </div>
    </div>
</div>
