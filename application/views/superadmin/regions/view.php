<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><?php echo e($region->name); ?> <span class="badge text-bg-light border ms-1"><?php echo e($region->code); ?></span></h4>
    <div>
        <a href="<?php echo site_url('superadmin/regions'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <?php if ($this->access_control->can('regions.edit')): ?>
        <a href="<?php echo site_url('superadmin/regions/edit/'.$region->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Region Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><?php echo e($region->code); ?></dd>
                    <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?php echo e($region->name); ?></dd>
                    <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?php echo e($region->description ?: '—'); ?></dd>
                    <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?php echo status_badge($region->status); ?></dd>
                    <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?php echo e($region->created_at); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card mb-3"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-teal"><i class="bi bi-diagram-3"></i></div>
            <div><div class="stat-value"><?php echo number_format($region->division_count); ?></div><div class="stat-label">Divisions</div></div>
        </div></div>
        <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-grad-slate"><i class="bi bi-building"></i></div>
            <div><div class="stat-value"><?php echo number_format($region->school_count); ?></div><div class="stat-label">Schools</div></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-diagram-3 me-2"></i>Divisions under this Region</div>
    <div class="table-responsive">
        <table class="table table-uie table-hover mb-0">
            <thead><tr><th>Code</th><th>Division</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if ($divisions): foreach ($divisions as $d): ?>
            <tr>
                <td><span class="badge text-bg-light border"><?php echo e($d->code); ?></span></td>
                <td><?php echo e($d->name); ?></td>
                <td><?php echo status_badge($d->status); ?></td>
                <td class="text-end"><a href="<?php echo site_url('superadmin/divisions/'.$d->id); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No divisions assigned yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
