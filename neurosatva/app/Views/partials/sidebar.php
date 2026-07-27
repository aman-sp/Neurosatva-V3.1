<?php $role = Auth::role(); ?>
<?php $pendingTutorVerifications = $role === 'admin' ? TutorRegistrationRequest::pendingCount() : 0; ?>
<?php $unreadAdminNotifications = $role === 'admin' ? AdminNotification::unreadCount() : 0; ?>
<aside class="sidebar" data-sidebar>
    <a class="brand" href="<?= e(path($role === 'admin' ? '/admin/dashboard' : '/tutor/dashboard')) ?>">
        <img class="site-logo sidebar-logo" src="<?= e(path('/assets/images/logo.svg')) ?>" alt="NeuroSatva">
    </a>

    <?php if ($role === 'admin'): ?>
        <nav>
            <a class="<?= active('/admin/dashboard') ?>" href="<?= e(path('/admin/dashboard')) ?>">
                <span>Dashboard</span>
                <?php if ($unreadAdminNotifications > 0): ?><span class="nav-badge"><?= e($unreadAdminNotifications) ?></span><?php endif; ?>
            </a>
            <a class="<?= active('/admin/tutors') ?>" href="<?= e(path('/admin/tutors')) ?>">Tutors</a>
            <a class="<?= active('/admin/registration-requests') ?>" href="<?= e(path('/admin/registration-requests')) ?>">
                <span>Registration Requests</span>
                <?php if ($pendingTutorVerifications > 0): ?><span class="nav-badge"><?= e($pendingTutorVerifications) ?></span><?php endif; ?>
            </a>
            <a class="<?= active('/admin/videos') ?>" href="<?= e(path('/admin/videos')) ?>">Videos</a>
            <a class="<?= active('/admin/assign-module') ?>" href="<?= e(path('/admin/assign-module')) ?>">Assign Module</a>
            <a class="<?= active('/admin/modules') ?>" href="<?= e(path('/admin/modules')) ?>">Digital Vault</a>
            <a class="<?= active('/admin/profile') ?>" href="<?= e(path('/admin/profile')) ?>">Profile</a>
        </nav>
    <?php else: ?>
        <nav>
            <a class="<?= active('/tutor/dashboard') ?>" href="<?= e(path('/tutor/dashboard')) ?>">Dashboard</a>
            <a class="<?= active('/tutor/videos') ?>" href="<?= e(path('/tutor/videos')) ?>">Videos</a>
            <a class="<?= active('/tutor/modules') ?>" href="<?= e(path('/tutor/modules')) ?>">Tutor Digital Vault</a>
            <a class="<?= active('/tutor/instructions') ?>" href="<?= e(path('/tutor/instructions')) ?>">Instructions</a>
            <a class="<?= active('/tutor/profile') ?>" href="<?= e(path('/tutor/profile')) ?>">Profile</a>
        </nav>
    <?php endif; ?>

    <div class="sidebar-note">
        <strong><?= e(Auth::user()['name'] ?? '') ?></strong>
        <span><?= e(Auth::user()['email'] ?? '') ?></span>
    </div>
</aside>
