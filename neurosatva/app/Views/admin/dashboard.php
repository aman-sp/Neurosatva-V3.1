<section class="stats-grid">
    <article class="stat-card"><span>Total Tutors</span><strong><?= e($totalTutors) ?></strong></article>
    <article class="stat-card"><span>Total Videos Received</span><strong><?= e($videoMetrics['total_received']) ?></strong></article>
    <article class="stat-card"><span>Verified Videos</span><strong><?= e($videoMetrics['verified']) ?></strong></article>
    <article class="stat-card"><span>Pending Verification</span><strong><?= e($videoMetrics['pending']) ?></strong></article>
    <a class="stat-card stat-link" href="<?= e(path('/admin/registration-requests?status=Pending')) ?>"><span>Pending Tutor Verifications</span><strong><?= e($pendingTutorRequests) ?></strong></a>
    <a class="stat-card stat-link" href="<?= e(path('/admin/registration-requests?status=Pending')) ?>"><span>Unread Notifications</span><strong><?= e($unreadNotifications) ?></strong></a>
</section>

<?php if (!empty($notifications)): ?>
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Admin Notifications</h2>
                <p>Latest tutor registration alerts for approval.</p>
            </div>
        </div>
        <div class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <a class="notification-item" href="<?= e(path($notification['link'] ?: '/admin/dashboard')) ?>">
                    <strong><?= e($notification['title']) ?></strong>
                    <span><?= e($notification['message']) ?></span>
                    <small><?= e(date('d M Y h:i A', strtotime($notification['created_at']))) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Tutor-wise video records</h2>
            <p>Recent tutors with assigned video counts.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>User ID</th><th>Tutor</th><th>Email</th><th>Personal Email</th><th>Phone</th><th>Gmail Status</th><th>Verified Date</th><th>School</th><th>Status</th><th>Videos</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($tutors as $tutor): ?>
                <tr>
                    <td><strong><?= e(tutor_user_id($tutor['id'])) ?></strong></td>
                    <td><?= e($tutor['name']) ?></td>
                    <td><?= e($tutor['email']) ?></td>
                    <td><?= e(($tutor['personal_email'] ?? '') ?: $tutor['email']) ?></td>
                    <td><?= e($tutor['phone'] ?: 'Not provided') ?></td>
                    <td><?= status_badge(Tutor::gmailStatus($tutor)) ?></td>
                    <td><?= !empty($tutor['gmail_verified_at']) ? e(date('d M Y', strtotime($tutor['gmail_verified_at']))) : e('Not verified') ?></td>
                    <td><?= e($tutor['school_name'] ?: 'Not provided') ?></td>
                    <td><?= status_badge($tutor['status']) ?></td>
                    <td><?= e($tutor['video_count']) ?></td>
                    <td><?= e(date('d M Y', strtotime($tutor['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
