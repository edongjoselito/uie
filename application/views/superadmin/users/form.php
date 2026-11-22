<div class="row justify-content-center">
<div class="col-lg-10">
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><?php echo $u ? 'Edit' : 'Create'; ?> User</h5>
        </div>
        <div class="card-body">
            <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>
            <?php echo form_open($u ? 'superadmin/users/edit/'.$u->id : 'superadmin/users/create'); ?>

            <div class="section-title">Account</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label required-label">Username</label>
                    <input type="text" name="username" class="form-control" maxlength="60" value="<?php echo e(set_value('username', $u->username ?? '')); ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label required-label">Email</label>
                    <input type="email" name="email" class="form-control" maxlength="120" value="<?php echo e(set_value('email', $u->email ?? '')); ?>" required>
                </div>
                <?php if ( ! $u): ?>
                <div class="col-md-6">
                    <label class="form-label required-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="col-md-6">
                    <label class="form-label required-label">Confirm Password</label>
                    <input type="password" name="password_confirm" class="form-control" required>
                </div>
                <div class="col-12"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>User will be required to change this password on first login.</small></div>
                <?php endif; ?>
            </div>

            <div class="section-title">Personal Details</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label required-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" maxlength="80" value="<?php echo e(set_value('first_name', $u->first_name ?? '')); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" maxlength="80" value="<?php echo e(set_value('middle_name', $u->middle_name ?? '')); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label required-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" maxlength="80" value="<?php echo e(set_value('last_name', $u->last_name ?? '')); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Position / Designation</label>
                    <input type="text" name="position" class="form-control" maxlength="100" value="<?php echo e(set_value('position', $u->position ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" maxlength="30" value="<?php echo e(set_value('contact_number', $u->contact_number ?? '')); ?>">
                </div>
            </div>

            <div class="section-title">Roles</div>
            <div class="row g-2 mb-3">
                <?php foreach ($roles as $r): ?>
                <div class="col-md-6">
                    <div class="form-check border rounded p-2 ps-5 h-100 role-check">
                        <input class="form-check-input role-checkbox" type="checkbox" name="role_ids[]" value="<?php echo $r->id; ?>" id="role<?php echo $r->id; ?>"
                               data-level="<?php echo e($r->level); ?>"
                               <?php echo in_array((int) $r->id, array_map('intval', (array) $user_role_ids), TRUE) ? 'checked' : ''; ?>>
                        <label class="form-check-label w-100" for="role<?php echo $r->id; ?>">
                            <span class="fw-semibold d-block"><?php echo e($r->name); ?></span>
                            <small class="text-muted"><?php echo e($r->description); ?> <span class="badge text-bg-light border"><?php echo e($r->level); ?></span></small>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="section-title">Organizational Assignment <small class="text-muted fw-normal">(required for Region / Division / School roles)</small></div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" id="regionLabel">Region</label>
                    <select name="region_id" id="regionSelect" class="form-select"
                            data-loads="#divisionSelect"
                            data-endpoint="<?php echo site_url('ajax/divisions-by-region/'); ?>">
                        <option value="">-- Select Region --</option>
                        <?php foreach ($regions as $r): ?>
                        <option value="<?php echo $r->id; ?>" <?php echo (int) $sel_region === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" id="divisionLabel">Division</label>
                    <select name="division_id" id="divisionSelect" class="form-select"
                            data-loads="#schoolSelect"
                            data-endpoint="<?php echo site_url('ajax/schools-by-division/'); ?>">
                        <option value="">-- Select Division --</option>
                        <?php foreach ($divisions as $d): ?>
                        <option value="<?php echo $d->id; ?>" <?php echo (int) $sel_division === (int) $d->id ? 'selected' : ''; ?>><?php echo e($d->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" id="schoolLabel">School</label>
                    <select name="school_id" id="schoolSelect" class="form-select">
                        <option value="">-- Select School --</option>
                        <?php foreach ($schools as $s): ?>
                        <option value="<?php echo $s->id; ?>" <?php echo (int) $sel_school === (int) $s->id ? 'selected' : ''; ?>><?php echo e($s->school_id.' - '.$s->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?php echo site_url('superadmin/users'); ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?php echo $u ? 'Save Changes' : 'Create User'; ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Highlight required assignment fields based on selected role levels
    function refreshAssignmentHints() {
        var levels = [];
        document.querySelectorAll('.role-checkbox:checked').forEach(function (cb) {
            levels.push(cb.dataset.level);
        });
        var need = {
            region:   levels.indexOf('region') > -1 || levels.indexOf('division') > -1 || levels.indexOf('school') > -1,
            division: levels.indexOf('division') > -1 || levels.indexOf('school') > -1,
            school:   levels.indexOf('school') > -1
        };
        [['regionLabel', 'region'], ['divisionLabel', 'division'], ['schoolLabel', 'school']].forEach(function (pair) {
            var el = document.getElementById(pair[0]);
            if (el) el.classList.toggle('required-label', need[pair[1]]);
        });
    }
    document.querySelectorAll('.role-checkbox').forEach(function (cb) {
        cb.addEventListener('change', refreshAssignmentHints);
    });
    refreshAssignmentHints();
});
</script>
