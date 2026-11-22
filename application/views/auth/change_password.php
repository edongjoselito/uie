<div class="auth-container">
    <div class="card shadow-lg border-0" style="max-width:460px;width:100%">
        <div class="card-body p-5">
            <h4 class="fw-bold mb-1"><i class="bi bi-key me-2"></i>Change Password</h4>
            <p class="text-muted small mb-4">Set a new password for your account.</p>

            <?php echo flash_html(); ?>
            <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

            <?php echo form_open('change-password', array('autocomplete' => 'off')); ?>
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password <small class="text-muted">(min. 8 characters)</small></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Password</button>
            <?php echo form_close(); ?>
            <a href="<?php echo site_url('dashboard'); ?>" class="d-block text-center small mt-3">Back to Dashboard</a>
        </div>
    </div>
</div>
