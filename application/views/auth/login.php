<main class="login-shell">
    <div class="login-card">
        <section class="login-brand" aria-label="About UIEIMS">
            <header class="login-identity">
                <div class="login-seals">
                    <img src="<?php echo base_url('assets/images/deped-seal.png'); ?>" alt="Department of Education seal" width="56" height="56">
                    <img src="<?php echo base_url('assets/images/region11-seal.jpg'); ?>" alt="DepEd Region XI seal" width="56" height="56">
                </div>
                <div>
                    <p class="login-department"><?php echo e(app_setting('deped_name', 'Department of Education')); ?></p>
                    <p class="login-region">REGION XI · DAVAO REGION</p>
                </div>
            </header>

            <div class="login-intro">
                <p class="login-system"><span class="login-rule" aria-hidden="true"></span>UIEIMS</p>
                <h1>Unified Inclusive Education Information and Management System</h1>
                <p class="login-description">One learner registry for inclusive education programs across the schools and divisions of Region XI.</p>
            </div>

            <div class="login-programs-block">
                <p class="login-programs-title">Programs covered</p>
                <ul class="login-programs">
                    <?php foreach (programs() as $p): ?>
                    <li class="login-program" style="--program-color: <?php echo e($p['color']); ?>">
                        <span class="login-program-icon"><i class="bi <?php echo e($p['icon']); ?>" aria-hidden="true"></i></span>
                        <span class="login-program-copy"><strong><?php echo e($p['code'] === 'spfl' ? 'FL / SPFL' : $p['short']); ?></strong><span><?php echo e($p['name']); ?></span></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-form-content">
                <header class="login-form-header">
                    <span class="login-access"><i class="bi bi-shield-lock" aria-hidden="true"></i> ACCOUNT ACCESS</span>
                    <h2 id="login-title">Welcome back</h2>
                    <p class="login-subtitle">Sign in with your UIEIMS account to continue.</p>
                </header>

                <div class="login-alerts">
                    <?php if ($this->input->get('password_reset') === 'success'): ?>
                    <div class="alert alert-success" role="status">Your password has been reset. Sign in with your new password.</div>
                    <?php endif; ?>
                    <?php echo flash_html(); ?>
                    <?php echo validation_errors('<div class="alert alert-danger py-2" role="alert">', '</div>'); ?>
                </div>

                <?php echo form_open('login', array('class' => 'login-form', 'id' => 'login-form')); ?>
                    <div class="login-field">
                        <label for="login-username">Username or Email</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input id="login-username" type="text" name="username" value="<?php echo e(set_value('username', '', FALSE)); ?>" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="120" required autofocus>
                        </div>
                    </div>
                    <div class="login-field">
                        <label for="login-password">Password</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input id="login-password" type="password" name="password" autocomplete="current-password" aria-describedby="login-caps" required>
                            <button class="login-password-toggle" type="button" aria-label="Show password" aria-controls="login-password" aria-pressed="false" hidden><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="login-row">
                        <span class="login-caps" id="login-caps" role="status" aria-live="polite"></span>
                        <a class="login-forgot" href="<?php echo site_url('forgot-password'); ?>">Forgot password?</a>
                    </div>
                    <button type="submit" class="login-submit"><span class="login-submit-label">Sign in</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                <?php echo form_close(); ?>

                <div class="login-meta">
                    <p><i class="bi bi-info-circle" aria-hidden="true"></i><span>Need an account or help signing in? Contact your school or division system administrator.</span></p>
                    <p><i class="bi bi-shield-check" aria-hidden="true"></i><span>Your information is protected under the Data Privacy Act of 2012 (RA 10173).</span></p>
                </div>
            </div>
        </section>
    </div>
    <footer class="login-footer">&copy; <?php echo date('Y'); ?> UIEIMS <span aria-hidden="true">·</span> <?php echo e(app_setting('region_office', 'DepEd Region XI')); ?></footer>
</main>
<script>
(function () {
    var toggle = document.querySelector('.login-password-toggle');
    var password = document.getElementById('login-password');
    var caps = document.getElementById('login-caps');
    var form = document.getElementById('login-form');
    toggle.hidden = false;
    toggle.addEventListener('click', function () {
        var visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.firstElementChild.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
        password.focus();
    });
    function updateCaps(event) {
        if (!event.getModifierState) return;
        caps.innerHTML = event.getModifierState('CapsLock') ? '<i class="bi bi-capslock-fill" aria-hidden="true"></i> Caps Lock is on' : '';
    }
    password.addEventListener('keyup', updateCaps);
    password.addEventListener('keydown', updateCaps);
    password.addEventListener('blur', function () { caps.textContent = ''; });
    form.addEventListener('submit', function () {
        var button = form.querySelector('.login-submit');
        if (button.disabled) return;
        setTimeout(function () {
            button.disabled = true;
            button.classList.add('is-loading');
            button.querySelector('.login-submit-label').textContent = 'Signing in…';
        }, 0);
    });
}());
</script>
