<div class="row justify-content-center">
<div class="col-lg-7">
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><?php echo $division ? 'Edit' : 'Add'; ?> Division</h5>
        </div>
        <div class="card-body">
            <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>
            <?php echo form_open($division ? 'superadmin/divisions/edit/'.$division->id : 'superadmin/divisions/create'); ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required-label">Division Code</label>
                    <input type="text" name="code" class="form-control" maxlength="20" value="<?php echo e(set_value('code', $division->code ?? '')); ?>" placeholder="e.g. DVO-CTY" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label required-label">Division Name</label>
                    <input type="text" name="name" class="form-control" maxlength="150" value="<?php echo e(set_value('name', $division->name ?? '')); ?>" placeholder="e.g. Division of Davao City" required>
                </div>
                <div class="col-12">
                    <label class="form-label required-label">Region</label>
                    <select name="region_id" class="form-select" required>
                        <option value="">-- Select Region --</option>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?php echo $r->id; ?>" <?php echo (int) set_value('region_id', $division->region_id ?? '') === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Assigns this Schools Division Office to a region. Schools under the division inherit the region automatically.</small>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?php echo site_url('superadmin/divisions'); ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?php echo $division ? 'Save Changes' : 'Create Division'; ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</div>
