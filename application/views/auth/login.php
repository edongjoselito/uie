<main class="login-shell">
    <div class="login-card">
        <section class="login-brand" aria-label="About UIEIMS">
            <div class="login-identity">
                <div class="login-seals">
                    <img src="<?php echo base_url('assets/images/deped-seal.png'); ?>" alt="Department of Education seal" width="64" height="64">
                    <img src="<?php echo base_url('assets/images/region11-seal.jpg'); ?>" alt="DepEd Region XI seal" width="64" height="64">
                </div>
                <p class="login-department"><?php echo e(app_setting('deped_name', 'Department of Education')); ?></p>
                <p class="login-region">REGION XI · DAVAO REGION</p>
            </div>
            <div class="login-intro">
                <span class="login-rule" aria-hidden="true"></span>
                <p class="login-system">UIEIMS</p>
                <h1>Unified Inclusive Education Information and Management System</h1>
            </div>
            <div class="login-programs" aria-label="Education programs">
                <?php foreach (programs() as $p): ?>
                <div class="login-program" style="--program-color: <?php echo e($p['color']); ?>" title="<?php echo e($p['name']); ?>">
                    <span class="login-program-icon"><i class="bi <?php echo e($p['icon']); ?>" aria-hidden="true"></i></span>
                    <span><?php echo e($p['short']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="login-slogan">&ldquo;<?php echo e(app_setting('region_slogan', 'Leading the Way')); ?>&rdquo;</p>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-form-content">
                <span class="login-access"><i class="bi bi-shield-lock" aria-hidden="true"></i> ACCOUNT ACCESS</span>
                <h2 id="login-title">Welcome back</h2>
                <p class="login-subtitle">Sign in to your UIEIMS account to continue.</p>

                <?php echo flash_html(); ?>
                <?php echo validation_errors('<div class="alert alert-danger py-2" role="alert">', '</div>'); ?>

                <?php echo form_open('login', array('class' => 'login-form')); ?>
                    <div class="login-field">
                        <label for="login-username">Username or email</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input id="login-username" type="text" name="username" value="<?php echo e(set_value('username', '', FALSE)); ?>" placeholder="Enter your username or email" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                        </div>
                    </div>
                    <div class="login-field">
                        <label for="login-password">Password</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input id="login-password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button class="login-password-toggle" type="button" aria-label="Show password" aria-controls="login-password" aria-pressed="false" hidden><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="login-submit">Sign in</button>
                <?php echo form_close(); ?>

                <div class="login-help"><i class="bi bi-info-circle" aria-hidden="true"></i><p>Need access or help signing in?<br>Contact your system administrator.</p></div>
            </div>
            <p class="login-privacy"><i class="bi bi-shield-check" aria-hidden="true"></i> Your information is protected under the<br>Data Privacy Act of 2012 (RA 10173).</p>
        </section>
    </div>
    <footer class="login-footer">&copy; <?php echo date('Y'); ?> UIEIMS <span aria-hidden="true">·</span> <?php echo e(app_setting('region_office', 'DepEd Region XI')); ?></footer>
</main>
<script>
(function () {
    var toggle = document.querySelector('.login-password-toggle');
    var password = document.getElementById('login-password');
    toggle.hidden = false;
    toggle.addEventListener('click', function () {
        var visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.firstElementChild.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}());
</script>
