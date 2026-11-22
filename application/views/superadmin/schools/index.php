<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-building me-2"></i>Schools</h4>
    <?php if ($this->access_control->can('schools.create')): ?>
    <a href="<?php echo site_url('superadmin/schools/create'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add School</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body pb-0">
        <form method="get" class="row g-2 mb-3 filter-form">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search ID, name, municipality..." value="<?php echo e($filters['q']); ?>">
            </div>
            <div class="col-md-3">
                <select name="region_id" id="filterRegion" class="form-select form-select-sm auto-submit">
                    <option value="">All Regions</option>
                    <?php foreach ($regions as $r): ?>
                    <option value="<?php echo $r->id; ?>" <?php echo (int) $filters['region_id'] === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="division_id" class="form-select form-select-sm auto-submit">
                    <option value="">All Divisions</option>
                    <?php foreach ($divisions as $d): ?>
                    <option value="<?php echo $d->id; ?>" <?php echo (int) $filters['division_id'] === (int) $d->id ? 'selected' : ''; ?>><?php echo e($d->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button>
                <a href="<?php echo site_url('superadmin/schools'); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-uie table-hover mb-0">
            <thead><tr><th>School ID</th><th>School Name</th><th>Division</th><th>Region</th><th>Municipality</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $s): ?>
            <tr>
                <td><span class="badge text-bg-light border"><?php echo e($s->school_id); ?></span></td>
                <td class="fw-semibold"><?php echo e($s->name); ?></td>
                <td class="text-muted"><?php echo e($s->division_name); ?></td>
                <td class="text-muted"><?php echo e($s->region_name); ?></td>
                <td class="text-muted"><?php echo e($s->municipality ?: '—'); ?></td>
                <td><?php echo status_badge($s->status); ?></td>
                <td class="text-end actions">
                    <a href="<?php echo site_url('superadmin/schools/'.$s->id); ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if ($this->access_control->can('schools.edit')): ?>
                    <a href="<?php echo site_url('superadmin/schools/edit/'.$s->id); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if ($this->access_control->can('schools.deactivate')): ?>
                    <?php echo form_open('superadmin/schools/toggle/'.$s->id, array('class' => 'd-inline', 'data-confirm' => ($s->status === 'active' ? 'Deactivate' : 'Activate').' this school?')); ?>
                        <button class="btn btn-sm btn-outline-<?php echo $s->status === 'active' ? 'danger' : 'success'; ?>" title="<?php echo $s->status === 'active' ? 'Deactivate' : 'Activate'; ?>">
                            <i class="bi bi-<?php echo $s->status === 'active' ? 'slash-circle' : 'check-circle'; ?>"></i>
                        </button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i>No schools found.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted"><?php echo number_format($total); ?> record(s)</small>
        <?php echo $pagination; ?>
    </div>
</div>
