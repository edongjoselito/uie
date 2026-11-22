<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2"></i>Schools Divisions</h4>
    <?php if ($this->access_control->can('divisions.create')): ?>
    <a href="<?php echo site_url('superadmin/divisions/create'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Division</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body pb-0">
        <form method="get" class="row g-2 mb-3 filter-form">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search code or name..." value="<?php echo e($filters['q']); ?>">
            </div>
            <div class="col-md-3">
                <select name="region_id" class="form-select form-select-sm auto-submit">
                    <option value="">All Regions</option>
                    <?php foreach ($regions as $r): ?>
                    <option value="<?php echo $r->id; ?>" <?php echo (int) $filters['region_id'] === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm auto-submit">
                    <option value="">All Statuses</option>
                    <option value="active"   <?php echo $filters['status'] === 'active'   ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search me-1"></i>Search</button>
                <a href="<?php echo site_url('superadmin/divisions'); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-uie table-hover mb-0">
            <thead><tr><th>Code</th><th>Division Name</th><th>Region</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $d): ?>
            <tr>
                <td><span class="badge text-bg-light border"><?php echo e($d->code); ?></span></td>
                <td class="fw-semibold"><?php echo e($d->name); ?></td>
                <td class="text-muted"><?php echo e($d->region_name); ?></td>
                <td><?php echo status_badge($d->status); ?></td>
                <td class="text-end actions">
                    <a href="<?php echo site_url('superadmin/divisions/'.$d->id); ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if ($this->access_control->can('divisions.edit')): ?>
                    <a href="<?php echo site_url('superadmin/divisions/edit/'.$d->id); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if ($this->access_control->can('divisions.deactivate')): ?>
                    <?php echo form_open('superadmin/divisions/toggle/'.$d->id, array('class' => 'd-inline', 'data-confirm' => ($d->status === 'active' ? 'Deactivate' : 'Activate').' this division?')); ?>
                        <button class="btn btn-sm btn-outline-<?php echo $d->status === 'active' ? 'danger' : 'success'; ?>" title="<?php echo $d->status === 'active' ? 'Deactivate' : 'Activate'; ?>">
                            <i class="bi bi-<?php echo $d->status === 'active' ? 'slash-circle' : 'check-circle'; ?>"></i>
                        </button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i>No divisions found.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted"><?php echo number_format($total); ?> record(s)</small>
        <?php echo $pagination; ?>
    </div>
</div>
