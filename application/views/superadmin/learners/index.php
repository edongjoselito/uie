<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-person-vcard me-2"></i>Centralized Learner Registry</h4>
    <?php if ($this->access_control->can('learners.create')): ?>
    <a href="<?php echo site_url('superadmin/learners/create'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Register Learner</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body pb-0">
        <form method="get" class="row g-2 mb-3 filter-form">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search LRN, name, school..." value="<?php echo e($filters['q']); ?>">
            </div>
            <div class="col-md-2">
                <select name="region_id" id="filterRegion" class="form-select form-select-sm"
                        data-loads="#filterDivision" data-endpoint="<?php echo site_url('ajax/divisions-by-region/'); ?>" onchange="this.form.submit()">
                    <option value="">All Regions</option>
                    <?php foreach ($regions as $r): ?>
                    <option value="<?php echo $r->id; ?>" <?php echo (int) $filters['region_id'] === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="division_id" id="filterDivision" class="form-select form-select-sm"
                        data-loads="#filterSchool" data-endpoint="<?php echo site_url('ajax/schools-by-division/'); ?>" onchange="this.form.submit()">
                    <option value="">All Divisions</option>
                    <?php foreach ($divisions as $d): ?>
                    <option value="<?php echo $d->id; ?>" <?php echo (int) $filters['division_id'] === (int) $d->id ? 'selected' : ''; ?>><?php echo e($d->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="school_id" id="filterSchool" class="form-select form-select-sm">
                    <option value="">All Schools</option>
                    <?php foreach ($schools as $s): ?>
                    <option value="<?php echo $s->id; ?>" <?php echo (int) $filters['school_id'] === (int) $s->id ? 'selected' : ''; ?>><?php echo e($s->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <select name="school_year_id" class="form-select form-select-sm">
                    <option value="">All School Years</option>
                    <?php foreach ($school_years as $sy): ?>
                    <option value="<?php echo $sy->id; ?>" <?php echo (int) $filters['school_year_id'] === (int) $sy->id ? 'selected' : ''; ?>>SY <?php echo e($sy->year_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="sex" class="form-select form-select-sm" style="max-width:110px">
                    <option value="">Sex</option>
                    <option value="Male"   <?php echo $filters['sex'] === 'Male'   ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $filters['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="max-width:140px">
                    <option value="active"   <?php echo $filters['status'] === 'active'   ? 'selected' : ''; ?>>Active</option>
                    <option value="archived" <?php echo $filters['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    <option value=""         <?php echo $filters['status'] === ''         ? 'selected' : ''; ?>>All Statuses</option>
                </select>
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search me-1"></i>Apply Filters</button>
                <a href="<?php echo site_url('superadmin/learners'); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-uie table-hover mb-0">
            <thead><tr><th>LRN</th><th>Learner Name</th><th>Sex</th><th class="text-center">Age</th><th>Grade</th><th>School</th><th>Division</th><th>SY</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $l): ?>
            <tr>
                <td><span class="badge text-bg-light border"><?php echo e($l->lrn ?: '—'); ?></span></td>
                <td class="fw-semibold"><?php echo e($l->last_name.', '.$l->first_name.($l->middle_name ? ' '.substr($l->middle_name, 0, 1).'.' : '').($l->extension_name ? ' '.$l->extension_name : '')); ?></td>
                <td><?php echo e($l->sex); ?></td>
                <td class="text-center"><?php echo learner_age($l->date_of_birth) !== NULL ? learner_age($l->date_of_birth) : '—'; ?></td>
                <td><?php echo e($l->grade_level ?: '—'); ?></td>
                <td><small><?php echo e($l->school_name ?: '<em class="text-muted">No current enrollment</em>'); ?></small></td>
                <td><small class="text-muted"><?php echo e($l->division_name); ?></small></td>
                <td><small class="text-muted"><?php echo e($l->year_label); ?></small></td>
                <td class="text-end actions text-nowrap">
                    <a href="<?php echo site_url('superadmin/learners/'.$l->id); ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if ($this->access_control->can('learners.edit')): ?>
                    <a href="<?php echo site_url('superadmin/learners/edit/'.$l->id); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if ($l->status === 'active' && $this->access_control->can('learners.archive')): ?>
                    <?php echo form_open('superadmin/learners/archive/'.$l->id, array('class' => 'd-inline', 'data-confirm' => 'Archive this learner record?')); ?>
                        <button class="btn btn-sm btn-outline-danger" title="Archive"><i class="bi bi-archive"></i></button>
                    <?php echo form_close(); ?>
                    <?php elseif ($l->status === 'archived' && $this->access_control->can('learners.restore')): ?>
                    <?php echo form_open('superadmin/learners/restore/'.$l->id, array('class' => 'd-inline', 'data-confirm' => 'Restore this learner record?')); ?>
                        <button class="btn btn-sm btn-outline-success" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i>No learner records found. Register the first learner to begin.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted"><?php echo number_format($total); ?> record(s)</small>
        <?php echo $pagination; ?>
    </div>
</div>
