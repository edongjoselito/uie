<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><?php echo e($division->name); ?> <span class="badge text-bg-light border ms-1"><?php echo e($division->code); ?></span></h4>
    <div>
        <a href="<?php echo site_url('superadmin/divisions'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <?php if ($this->access_control->can('divisions.edit')): ?>
        <a href="<?php echo site_url('superadmin/divisions/edit/'.$division->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Division Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><?php echo e($division->code); ?></dd>
                    <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?php echo e($division->name); ?></dd>
                    <dt class="col-sm-3">Region</dt><dd class="col-sm-9"><?php echo e($division->region_name); ?></dd>
                    <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?php echo status_badge($division->status); ?></dd>
                    <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?php echo e($division->created_at); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card mb-3"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-slate"><i class="bi bi-building"></i></div>
            <div><div class="stat-value"><?php echo number_format($division->school_count); ?></div><div class="stat-label">Schools</div></div>
        </div></div>
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-primary"><i class="bi bi-person-vcard"></i></div>
            <div><div class="stat-value"><?php echo number_format($division->learner_count); ?></div><div class="stat-label">Learners</div></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2"></i>Schools under this Division</div>
    <div class="table-responsive">
        <table class="table table-uie table-hover mb-0">
            <thead><tr><th>School ID</th><th>School Name</th><th>Municipality</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if ($schools): foreach ($schools as $s): ?>
            <tr>
                <td><span class="badge text-bg-light border"><?php echo e($s->school_id); ?></span></td>
                <td><?php echo e($s->name); ?></td>
                <td class="text-muted"><?php echo e($s->municipality ?: '—'); ?></td>
                <td><?php echo status_badge($s->status); ?></td>
                <td class="text-end"><a href="<?php echo site_url('superadmin/schools/'.$s->id); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No schools assigned yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
