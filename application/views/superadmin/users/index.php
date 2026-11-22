<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>User Management</h4>
    <?php if ($this->access_control->can('users.create')): ?>
    <a href="<?php echo site_url('superadmin/users/create'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create User</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body pb-0">
        <form method="get" class="row g-2 mb-3 filter-form">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search username, email, name..." value="<?php echo e($filters['q']); ?>">
            </div>
            <div class="col-md-3">
                <select name="role_id" class="form-select form-select-sm auto-submit">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?php echo $r->id; ?>" <?php echo (int) $filters['role_id'] === (int) $r->id ? 'selected' : ''; ?>><?php echo e($r->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm auto-submit">
                    <option value="">All Statuses</option>
                    <option value="active"   <?php echo $filters['status'] === 'active'   ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="locked"   <?php echo $filters['status'] === 'locked'   ? 'selected' : ''; ?>>Locked</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search me-1"></i>Search</button>
                <a href="<?php echo site_url('superadmin/users'); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-uie table-hover mb-0">
            <thead><tr><th>User</th><th>Username</th><th>Role(s)</th><th>Assignment</th><th>Last Login</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $u): ?>
            <tr>
                <td>
                    <div class="fw-semibold"><?php echo e($u->first_name.' '.$u->last_name); ?></div>
                    <small class="text-muted"><?php echo e($u->email); ?></small>
                </td>
                <td><?php echo e($u->username); ?></td>
                <td><small><?php echo e($u->role_names ?: '—'); ?></small></td>
                <td>
                    <small class="text-muted">
                    <?php
                    if ($u->assignment) {
                        $parts = array_filter(array($u->assignment['school_name'], $u->assignment['division_name'], $u->assignment['region_name']));
                        echo e($parts ? implode(' · ', $parts) : '—');
                    } else echo '—';
                    ?>
                    </small>
                </td>
                <td><small class="text-muted"><?php echo $u->last_login_at ? e(date('M d, Y H:i', strtotime($u->last_login_at))) : 'Never'; ?></small></td>
                <td><?php echo status_badge($u->status); ?></td>
                <td class="text-end actions text-nowrap">
                    <a href="<?php echo site_url('superadmin/users/'.$u->id); ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if ($this->access_control->can('users.edit')): ?>
                    <a href="<?php echo site_url('superadmin/users/edit/'.$u->id); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if ($u->status === 'locked' && $this->access_control->can('users.edit')): ?>
                    <?php echo form_open('superadmin/users/unlock/'.$u->id, array('class' => 'd-inline')); ?>
                        <button class="btn btn-sm btn-outline-warning" title="Unlock"><i class="bi bi-unlock"></i></button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                    <?php if ($this->access_control->can('users.reset_password')): ?>
                    <?php echo form_open('superadmin/users/reset_password/'.$u->id, array('class' => 'd-inline', 'data-confirm' => 'Reset password for '.$u->username.'?')); ?>
                        <button class="btn btn-sm btn-outline-info" title="Reset Password"><i class="bi bi-key"></i></button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                    <?php if ($this->access_control->can('users.deactivate') && (int) $u->id !== (int) $user['id']): ?>
                    <?php echo form_open('superadmin/users/toggle/'.$u->id, array('class' => 'd-inline', 'data-confirm' => ($u->status === 'active' ? 'Deactivate' : 'Activate').' this account?')); ?>
                        <button class="btn btn-sm btn-outline-<?php echo $u->status === 'active' ? 'danger' : 'success'; ?>" title="<?php echo $u->status === 'active' ? 'Deactivate' : 'Activate'; ?>">
                            <i class="bi bi-<?php echo $u->status === 'active' ? 'slash-circle' : 'check-circle'; ?>"></i>
                        </button>
                    <?php echo form_close(); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i>No users found.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted"><?php echo number_format($total); ?> record(s)</small>
        <?php echo $pagination; ?>
    </div>
</div>
