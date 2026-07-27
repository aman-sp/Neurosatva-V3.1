<?php $showRegister = isset($_GET['register']); ?>
<section class="auth-flip-shell <?= $showRegister ? 'show-register' : '' ?>" data-auth-card>
    <div class="auth-flip-card" data-flip-card>
        <section class="auth-face auth-face-front auth-panel login-panel" data-login-panel aria-hidden="<?= $showRegister ? 'true' : 'false' ?>" <?= $showRegister ? 'inert' : '' ?>>
            <img class="site-logo auth-wordmark" src="<?= e(path('/assets/images/logo.svg')) ?>" alt="NeuroSatva">
            <div class="auth-copy">
                <h2>Welcome Back</h2>
                <p>Sign in to continue</p>
            </div>
            <form class="glass-form" method="post" action="<?= e(path('/admin/login')) ?>" data-loading-form data-auth-submit>
                <?= csrf_field() ?>
                <label class="glass-field">
                    <span>User ID/Email Address</span>
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>
                    </span>
                    <input name="email" required autocomplete="username" placeholder="User ID/Email Address">
                </label>
                <label class="glass-field">
                    <span>Password</span>
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg>
                    </span>
                    <input type="password" name="password" required autocomplete="current-password" placeholder="Password">
                </label>
                <a class="forgot-link" href="<?= e(path('/admin/login')) ?>">Forgot Password</a>
                <button class="glass-button primary" type="submit" data-loading-button>
                    <span class="button-text">Login</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>
            <p class="glass-switch">Don't have an account? <button type="button" data-show-register>Register</button></p>
        </section>

        <section class="auth-face auth-face-back auth-panel register-panel" data-register-panel aria-hidden="<?= $showRegister ? 'false' : 'true' ?>" <?= $showRegister ? '' : 'inert' ?>>
            <img class="site-logo auth-wordmark" src="<?= e(path('/assets/images/logo.svg')) ?>" alt="NeuroSatva">
            <form class="glass-form register-form" method="post" action="<?= e(path('/tutor/register')) ?>" data-loading-form data-auth-submit>
                <?= csrf_field() ?>
                <div class="auth-field-row">
                    <label class="glass-field">
                        <span>Full Name</span>
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input name="full_name" required maxlength="120" autocomplete="name" placeholder=" ">
                    </label>
                    <label class="glass-field">
                        <span>Personal Email Address</span>
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>
                        </span>
                        <input type="email" name="email" required maxlength="190" autocomplete="email" placeholder=" ">
                    </label>
                </div>
                <div class="auth-field-row">
                    <label class="glass-field">
                        <span>Phone Number</span>
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.59 2.61a2 2 0 0 1-.45 2.11L8 9.7a16 16 0 0 0 6.3 6.3l1.26-1.25a2 2 0 0 1 2.11-.45c.84.27 1.71.47 2.61.59A2 2 0 0 1 22 16.92z"></path></svg>
                        </span>
                        <input type="tel" name="phone" required maxlength="20" autocomplete="tel" placeholder=" ">
                    </label>
                    <label class="glass-field">
                        <span>School Name (Optional)</span>
                        <span class="field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 22h18"></path><path d="M6 18V8l6-4 6 4v10"></path><path d="M10 18v-5h4v5"></path><path d="M8 10h.01"></path><path d="M16 10h.01"></path></svg>
                        </span>
                        <input name="school_name" maxlength="160" placeholder=" ">
                    </label>
                </div>
                <label class="glass-field">
                    <span>Gender (Optional)</span>
                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M12 12v8"></path><path d="M8 16h8"></path></svg>
                    </span>
                    <select name="gender">
                        <option value="">Select gender</option>
                        <option>Female</option>
                        <option>Male</option>
                        <option>Non-binary</option>
                        <option>Prefer not to say</option>
                    </select>
                </label>
                <button class="glass-button primary register-submit" type="submit" data-loading-button>
                    <span class="button-text">Register</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>
            <p class="glass-switch">Already have an account? <button type="button" data-show-login>Login</button></p>
        </section>

        <div class="auth-success-plane" data-auth-success aria-hidden="true">
            <div class="success-check" aria-hidden="true"></div>
        </div>
    </div>
</section>

<?php if (isset($_GET['registered'])): ?>
    <div class="success-popup" data-success-popup>
        <div class="success-popup-card">
            <div class="success-check" aria-hidden="true"></div>
            <h2>Registration Submitted Successfully</h2>
            <p>Your registration request has been submitted successfully.</p>
            <?php if (isset($_GET['request_id'])): ?>
                <p><strong>Application ID: NS-TUTOR-<?= e(str_pad((string) $_GET['request_id'], 5, '0', STR_PAD_LEFT)) ?></strong></p>
            <?php endif; ?>
            <p>Your account is currently under Admin review.</p>
            <p>Once approved, you will be assigned a Tutor User ID and can log in using either that ID or your email address.</p>
            <div class="success-actions">
                <button class="glass-button secondary" type="button" data-close-popup>OK</button>
            </div>
        </div>
    </div>
<?php endif; ?>
