<?php $role = Auth::role(); ?>
<?php $pendingTutorVerifications = $role === 'admin' ? TutorRegistrationRequest::pendingCount() : 0; ?>
<?php $unreadAdminNotifications = $role === 'admin' ? AdminNotification::unreadCount() : 0; ?>
<aside class="sidebar" data-sidebar>
    <a class="brand" href="<?= e(path($role === 'admin' ? '/admin/dashboard' : '/tutor/dashboard')) ?>">
        <img class="site-logo sidebar-logo" src="<?= e(path('/assets/images/logo.svg')) ?>" alt="NeuroSatva">
    </a>
    <div class="sidebar-divider"></div>

    <?php if ($role === 'admin'): ?>
        <nav>
            <a class="<?= active('/admin/dashboard') ?>" href="<?= e(path('/admin/dashboard')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </span>
                <span class="nav-label">Dashboard</span>
                <?php if ($unreadAdminNotifications > 0): ?><span class="nav-badge"><?= e($unreadAdminNotifications) ?></span><?php endif; ?>
            </a>
            <a class="<?= active('/admin/tutors') ?>" href="<?= e(path('/admin/tutors')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <span class="nav-label">Manage Tutors</span>
            </a>
            <a class="<?= active('/admin/registration-requests') ?>" href="<?= e(path('/admin/registration-requests')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span class="nav-label">Tutor Verification</span>
                <?php if ($pendingTutorVerifications > 0): ?><span class="nav-badge"><?= e($pendingTutorVerifications) ?></span><?php endif; ?>
            </a>
            <a class="<?= active('/admin/videos') ?>" href="<?= e(path('/admin/videos')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                </span>
                <span class="nav-label">Video Verification</span>
            </a>
            <a class="<?= active('/admin/assign') ?>" href="<?= e(path('/admin/assign')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M13 6h3a2 2 0 0 1 2 2v7"/><line x1="6" y1="9" x2="6" y2="21"/></svg>
                </span>
                <span class="nav-label">Assign Module</span>
            </a>
            <a class="<?= active('/admin/vault') ?>" href="<?= e(path('/admin/vault')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </span>
                <span class="nav-label">Digital Vault</span>
            </a>
            <a class="<?= active('/admin/profile') ?>" href="<?= e(path('/admin/profile')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                </span>
                <span class="nav-label">Settings</span>
            </a>
        </nav>
    <?php else: ?>
        <nav>
            <a class="<?= active('/tutor/dashboard') ?>" href="<?= e(path('/tutor/dashboard')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a class="<?= active('/tutor/videos') ?>" href="<?= e(path('/tutor/videos')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                </span>
                <span class="nav-label">My Verified Videos</span>
            </a>
            <a class="<?= active('/tutor/vault') ?>" href="<?= e(path('/tutor/vault')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </span>
                <span class="nav-label">Tutor Digital Vault</span>
            </a>
            <a class="<?= active('/tutor/instructions') ?>" href="<?= e(path('/tutor/instructions')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                </span>
                <span class="nav-label">Upload Link</span>
            </a>
            <a class="<?= active('/tutor/profile') ?>" href="<?= e(path('/tutor/profile')) ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <span class="nav-label">Profile</span>
            </a>
        </nav>
    <?php endif; ?>

    <div class="sidebar-user">
        <div class="sidebar-avatar"><?= e(mb_strtoupper(mb_substr(Auth::user()['name'] ?? 'A', 0, 1))) ?></div>
        <div class="sidebar-user-info">
            <strong><?= e(Auth::user()['name'] ?? '') ?></strong>
            <span><?= e(Auth::user()['email'] ?? '') ?></span>
        </div>
    </div>
</aside>
