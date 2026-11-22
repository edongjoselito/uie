<div class="row justify-content-center">
<div class="col-lg-9">
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><?php echo $school ? 'Edit' : 'Add'; ?> School</h5>
        </div>
        <div class="card-body">
            <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>
            <?php echo form_open($school ? 'superadmin/schools/edit/'.$school->id : 'superadmin/schools/create'); ?>

            <div class="section-title">School Identity</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label required-label">DepEd School ID</label>
                    <input type="text" name="school_id" class="form-control" maxlength="20" value="<?php echo e(set_value('school_id', $school->school_id ?? '')); ?>" placeholder="e.g. 129302" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label required-label">School Name</label>
                    <input type="text" name="name" class="form-control" maxlength="200" value="<?php echo e(set_value('name', $school->name ?? '')); ?>" required>
                </div>
            </div>

            <div class="section-title">Organizational Assignment</div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Region</label>
                    <select id="regionSelect" class="form-select"
                            data-loads="#divisionSelect"
                            data-endpoint="<?php echo site_url('ajax/divisions-by-region/'); ?>">
                        <option value="">-- Select Region --</option>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?php echo $r->id; ?>" <?php echo (int) $sel_region === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Helper filter — the school inherits its division's region.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label required-label">Division</label>
                    <select name="division_id" id="divisionSelect" class="form-select" required>
                        <option value="">-- Select Division --</option>
                        <?php foreach ($divisions as $d): ?>
                        <option value="<?php echo $d->id; ?>" <?php echo (int) set_value('division_id', $school->division_id ?? '') === (int) $d->id ? 'selected' : ''; ?>><?php echo e($d->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="section-title">Location &amp; Classification</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Municipality / City</label>
                    <input type="text" name="municipality" class="form-control" maxlength="120" value="<?php echo e(set_value('municipality', $school->municipality ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Barangay</label>
                    <input type="text" name="barangay" class="form-control" maxlength="120" value="<?php echo e(set_value('barangay', $school->barangay ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">School Classification</label>
                    <select name="classification" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (array('Elementary','Junior High School','Senior High School','Integrated School (K-12)','Integrated School (K-10)') as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo set_value('classification', $school->classification ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">School Type</label>
                    <select name="school_type" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach (array('Public','Private','SUC/LUC') as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo set_value('school_type', $school->school_type ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?php echo site_url('superadmin/schools'); ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?php echo $school ? 'Save Changes' : 'Create School'; ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</div>
