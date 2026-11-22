<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><?php echo e($school->name); ?> <span class="badge text-bg-light border ms-1"><?php echo e($school->school_id); ?></span></h4>
    <div>
        <a href="<?php echo site_url('superadmin/schools'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <?php if ($this->access_control->can('schools.edit')): ?>
        <a href="<?php echo site_url('superadmin/schools/edit/'.$school->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">School Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">DepEd School ID</dt><dd class="col-sm-8"><?php echo e($school->school_id); ?></dd>
                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?php echo e($school->name); ?></dd>
                    <dt class="col-sm-4">Region</dt><dd class="col-sm-8"><?php echo e($school->region_name); ?></dd>
                    <dt class="col-sm-4">Division</dt><dd class="col-sm-8"><?php echo e($school->division_name); ?></dd>
                    <dt class="col-sm-4">Municipality</dt><dd class="col-sm-8"><?php echo e($school->municipality ?: '—'); ?></dd>
                    <dt class="col-sm-4">Barangay</dt><dd class="col-sm-8"><?php echo e($school->barangay ?: '—'); ?></dd>
                    <dt class="col-sm-4">Classification</dt><dd class="col-sm-8"><?php echo e($school->classification ?: '—'); ?></dd>
                    <dt class="col-sm-4">Type</dt><dd class="col-sm-8"><?php echo e($school->school_type ?: '—'); ?></dd>
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?php echo status_badge($school->status); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-primary"><i class="bi bi-person-vcard"></i></div>
            <div><div class="stat-value"><?php echo number_format($school->learner_count); ?></div><div class="stat-label">Active Learners</div></div>
        </div></div>
    </div>
</div>
