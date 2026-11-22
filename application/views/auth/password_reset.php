<main class="login-shell recovery-shell">
    <section class="login-form-panel recovery-card" aria-labelledby="recovery-title">
        <span class="login-access"><i class="bi bi-shield-lock" aria-hidden="true"></i> ACCOUNT RECOVERY</span>
        <h1 id="recovery-title"><?php echo $reset_mode ? 'Reset password' : 'Forgot password?'; ?></h1>
        <p class="login-subtitle"><?php echo $reset_mode ? 'Choose a new password for your UIEIMS account.' : 'Enter your registered email address to request a secure reset link.'; ?></p>
        <?php echo flash_html(); ?>
        <?php echo validation_errors('<div class="alert alert-danger" role="alert">', '</div>'); ?>
        <?php if ( ! empty($reset_error)): ?>
        <div class="alert alert-danger" role="alert"><?php echo e($reset_error); ?></div>
        <?php endif; ?>

        <?php if ( ! $reset_mode): ?>
        <?php echo form_open('forgot-password', array('class' => 'login-form')); ?>
            <div class="login-field">
                <label for="recovery-email">Email Address</label>
                <div class="login-input-wrap"><input id="recovery-email" type="email" name="email" maxlength="120" autocomplete="email" required></div>
            </div>
            <button class="login-submit" type="submit">Send reset link</button>
        <?php echo form_close(); ?>
        <?php elseif ($token_valid): ?>
        <?php echo form_open('reset-password', array('class' => 'login-form')); ?>
            <div class="login-field">
                <label for="new-password">New Password</label>
                <p class="small text-muted" id="password-guidance">Use at least 12 characters, up to 72 bytes. A unique passphrase is recommended.</p>
                <div class="login-input-wrap"><input id="new-password" type="password" name="new_password" minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-guidance" required></div>
            </div>
            <div class="login-field">
                <label for="confirm-password">Confirm New Password</label>
                <div class="login-input-wrap"><input id="confirm-password" type="password" name="confirm_password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
            </div>
            <button class="login-submit" type="submit">Reset password</button>
        <?php echo form_close(); ?>
        <?php else: ?>
        <p class="login-subtitle">Open the link from your reset email. If it has expired or was already used, request a new one.</p>
        <?php endif; ?>

        <?php if ($reset_mode): ?>
        <a class="login-forgot" href="<?php echo site_url('forgot-password'); ?>">Request a new reset link</a>
        <?php echo form_open('reset-password', array('id' => 'reset-token-form', 'hidden' => 'hidden')); ?>
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="token" id="reset-token">
        <?php echo form_close(); ?>
        <noscript><p class="mt-3">JavaScript is required to open your secure reset link. Enable it and reopen the email link.</p></noscript>
        <script>
        (function () {
            var token = window.location.hash.slice(1);
            if (!token) return;
            window.history.replaceState(null, '', window.location.pathname);
            document.getElementById('reset-token').value = token;
            document.getElementById('reset-token-form').submit();
        }());
        </script>
        <?php endif; ?>
        <a class="login-forgot" href="<?php echo site_url('login'); ?>">Back to sign in</a>
    </section>
</main>
