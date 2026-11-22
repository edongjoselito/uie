<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">
            <?php echo e($learner->last_name.', '.$learner->first_name.($learner->middle_name ? ' '.substr($learner->middle_name, 0, 1).'.' : '').($learner->extension_name ? ' '.$learner->extension_name : '')); ?>
        </h4>
        <small class="text-muted">
            LRN: <span class="badge text-bg-light border"><?php echo e($learner->lrn ?: 'Not set'); ?></span>
            <?php echo status_badge($learner->status); ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo site_url('superadmin/learners'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <?php if ($this->access_control->can('learners.edit')): ?>
        <a href="<?php echo site_url('superadmin/learners/edit/'.$learner->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Personal Information</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Sex</dt><dd class="col-sm-8"><?php echo e($learner->sex); ?></dd>
                    <dt class="col-sm-4">Date of Birth</dt><dd class="col-sm-8"><?php echo e($learner->date_of_birth ?: '—'); ?><?php echo learner_age($learner->date_of_birth) !== NULL ? ' ('.learner_age($learner->date_of_birth).' yrs old)' : ''; ?></dd>
                    <dt class="col-sm-4">Place of Birth</dt><dd class="col-sm-8"><?php echo e($learner->place_of_birth ?: '—'); ?></dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?php echo e($learner->address ?: '—'); ?></dd>
                    <dt class="col-sm-4">Barangay</dt><dd class="col-sm-8"><?php echo e($learner->barangay ?: '—'); ?></dd>
                    <dt class="col-sm-4">Municipality</dt><dd class="col-sm-8"><?php echo e($learner->municipality ?: '—'); ?></dd>
                    <dt class="col-sm-4">Province</dt><dd class="col-sm-8"><?php echo e($learner->province ?: '—'); ?></dd>
                    <dt class="col-sm-4">Contact</dt><dd class="col-sm-8"><?php echo e($learner->contact_number ?: '—'); ?></dd>
                </dl>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Parent / Guardian</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Father</dt><dd class="col-sm-8"><?php echo e($learner->father_name ?: '—'); ?></dd>
                    <dt class="col-sm-4">Mother</dt><dd class="col-sm-8"><?php echo e($learner->mother_name ?: '—'); ?></dd>
                    <dt class="col-sm-4">Guardian</dt><dd class="col-sm-8"><?php echo e($learner->guardian_name ?: '—'); ?><?php echo $learner->guardian_relationship ? ' ('.e($learner->guardian_relationship).')' : ''; ?></dd>
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white fw-semibold">Remarks</div>
            <div class="card-body small text-muted"><?php echo e($learner->remarks ?: '—'); ?></div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2"></i>Enrollment History</div>
            <div class="table-responsive">
                <table class="table table-uie mb-0">
                    <thead><tr><th>SY</th><th>School</th><th>Grade</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if ($learner->enrollments): foreach ($learner->enrollments as $en): ?>
                    <tr <?php echo $en->is_current ? 'class="table-primary"' : ''; ?>>
                        <td class="text-nowrap"><?php echo e($en->year_label); ?><?php echo $en->is_current ? ' <span class="badge text-bg-primary">current</span>' : ''; ?></td>
                        <td><small><?php echo e($en->school_name); ?><br><span class="text-muted"><?php echo e($en->division_name); ?></span></small></td>
                        <td><?php echo e(($en->grade_level ?: '—').($en->section ? ' / '.$en->section : '')); ?></td>
                        <td><small><?php echo e($en->enrollment_status); ?></small></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No enrollment records.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-puzzle me-2"></i>Program Profiles</div>
            <div class="card-body small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                IPEd, Madrasah, and SNED program profiles attach to this learner in Phases 4–6.
                One learner may participate in multiple programs — no duplicate identity records are created.
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Record History</div>
            <div class="table-responsive" style="max-height:280px;overflow:auto">
                <table class="table table-uie mb-0">
                    <thead><tr><th>When</th><th>Change</th><th>Table</th></tr></thead>
                    <tbody>
                    <?php if ($learner->history): foreach ($learner->history as $h): ?>
                    <tr>
                        <td class="text-nowrap text-muted"><small><?php echo e($h->created_at); ?></small></td>
                        <td><span class="badge text-bg-light border"><?php echo e($h->change_type); ?></span></td>
                        <td><small class="text-muted"><?php echo e($h->table_name.' #'.$h->record_id); ?></small></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">No history yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
