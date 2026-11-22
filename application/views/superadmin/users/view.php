<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><?php echo e($u->first_name.' '.$u->last_name); ?> <small class="text-muted">(<?php echo e($u->username); ?>)</small></h4>
    <div>
        <a href="<?php echo site_url('superadmin/users'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <?php if ($this->access_control->can('users.edit')): ?>
        <a href="<?php echo site_url('superadmin/users/edit/'.$u->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Account Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Username</dt><dd class="col-sm-8"><?php echo e($u->username); ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?php echo e($u->email); ?></dd>
                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?php echo e(trim($u->first_name.' '.($u->middle_name ? $u->middle_name.' ' : '').$u->last_name)); ?></dd>
                    <dt class="col-sm-4">Position</dt><dd class="col-sm-8"><?php echo e($u->position ?: '—'); ?></dd>
                    <dt class="col-sm-4">Contact</dt><dd class="col-sm-8"><?php echo e($u->contact_number ?: '—'); ?></dd>
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?php echo status_badge($u->status); ?></dd>
                    <dt class="col-sm-4">Last Login</dt><dd class="col-sm-8"><?php echo $u->last_login_at ? e($u->last_login_at).' from '.e($u->last_login_ip) : 'Never'; ?></dd>
                    <dt class="col-sm-4">Must Change PW</dt><dd class="col-sm-8"><?php echo $u->must_change_password ? '<span class="badge text-bg-warning">Yes</span>' : 'No'; ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Roles &amp; Assignment</div>
            <div class="card-body">
                <h6 class="small text-muted text-uppercase">Roles</h6>
                <?php if ($u->roles): foreach ($u->roles as $r): ?>
                    <span class="badge text-bg-primary me-1 mb-1"><?php echo e($r['name']); ?></span>
                <?php endforeach; else: ?><p class="text-muted small">No roles assigned.</p><?php endif; ?>
                <h6 class="small text-muted text-uppercase mt-3">Assignment</h6>
                <?php if ($u->assignment): ?>
                <dl class="row mb-0 small">
                    <?php if ($u->assignment->region_name): ?><dt class="col-4">Region</dt><dd class="col-8"><?php echo e($u->assignment->region_name); ?></dd><?php endif; ?>
                    <?php if ($u->assignment->division_name): ?><dt class="col-4">Division</dt><dd class="col-8"><?php echo e($u->assignment->division_name); ?></dd><?php endif; ?>
                    <?php if ($u->assignment->school_name): ?><dt class="col-4">School</dt><dd class="col-8"><?php echo e($u->assignment->deped_school_id.' - '.$u->assignment->school_name); ?></dd><?php endif; ?>
                </dl>
                <?php else: ?><p class="text-muted small">System-level account (no organizational restriction).</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Activity</div>
    <div class="table-responsive">
        <table class="table table-uie mb-0">
            <thead><tr><th>When</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php if ($activity): foreach ($activity as $a): ?>
            <tr>
                <td class="text-nowrap text-muted"><?php echo e($a->created_at); ?></td>
                <td><span class="badge text-bg-light border"><?php echo e($a->action); ?></span></td>
                <td class="text-muted"><?php echo e($a->module); ?></td>
                <td class="small text-muted"><?php echo e($a->description); ?></td>
                <td class="small text-muted"><?php echo e($a->ip_address); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No activity yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
