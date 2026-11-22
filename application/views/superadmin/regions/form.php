<div class="row justify-content-center">
<div class="col-lg-7">
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><?php echo $region ? 'Edit' : 'Add'; ?> Region</h5>
        </div>
        <div class="card-body">
            <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>
            <?php echo form_open($region ? 'superadmin/regions/edit/'.$region->id : 'superadmin/regions/create'); ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required-label">Region Code</label>
                    <input type="text" name="code" class="form-control" maxlength="20" value="<?php echo e(set_value('code', $region->code ?? '')); ?>" placeholder="e.g. R-XI" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label required-label">Region Name</label>
                    <input type="text" name="name" class="form-control" maxlength="150" value="<?php echo e(set_value('name', $region->name ?? '')); ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" maxlength="1000"><?php echo e(set_value('description', $region->description ?? '')); ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?php echo site_url('superadmin/regions'); ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?php echo $region ? 'Save Changes' : 'Create Region'; ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</div>
