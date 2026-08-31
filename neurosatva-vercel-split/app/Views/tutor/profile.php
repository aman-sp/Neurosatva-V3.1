<section class="panel narrow">
    <div class="panel-head">
        <div>
            <h2>Profile</h2>
            <p>Your account is managed by the admin. Contact admin for changes.</p>
        </div>
    </div>
    <dl class="profile-list">
        <dt>User ID</dt><dd><?= e(tutor_user_id($tutor['id'])) ?></dd>
        <dt>Name</dt><dd><?= e($tutor['name']) ?></dd>
        <dt>Email</dt><dd><?= e($tutor['email']) ?></dd>
        <dt>Personal Email</dt><dd><?= e(($tutor['personal_email'] ?? '') ?: $tutor['email']) ?></dd>
        <dt>Phone</dt><dd><?= e($tutor['phone'] ?: 'Not provided') ?></dd>
        <dt>Gmail Status</dt><dd><?= status_badge(Tutor::gmailStatus($tutor)) ?></dd>
        <dt>Gmail Verified</dt><dd><?= !empty($tutor['gmail_verified_at']) ? e(date('d M Y h:i A', strtotime($tutor['gmail_verified_at']))) : e('Not verified') ?></dd>
        <dt>School</dt><dd><?= e($tutor['school_name'] ?: 'Not provided') ?></dd>
        <dt>Gender</dt><dd><?= e($tutor['gender'] ?: 'Not provided') ?></dd>
        <dt>Status</dt><dd><?= status_badge($tutor['status']) ?></dd>
        <dt>Created</dt><dd><?= e(date('d M Y', strtotime($tutor['created_at']))) ?></dd>
    </dl>
</section>
