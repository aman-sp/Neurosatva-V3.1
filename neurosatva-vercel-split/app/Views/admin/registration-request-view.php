<section class="panel narrow">
    <div class="panel-head">
        <div>
            <h2><?= e($request['full_name']) ?></h2>
            <p>Registration request details.</p>
        </div>
        <?= status_badge($request['status']) ?>
    </div>

    <dl class="profile-list">
        <dt>Name</dt><dd><?= e($request['full_name']) ?></dd>
        <dt>Email</dt><dd><?= e($request['email']) ?></dd>
        <dt>Phone</dt><dd><?= e($request['phone'] ?? 'Not provided') ?></dd>
        <dt>School</dt><dd><?= e($request['school_name']) ?></dd>
        <dt>Gender</dt><dd><?= e($request['gender']) ?></dd>
        <dt>Registered</dt><dd><?= e(date('d M Y h:i A', strtotime($request['created_at']))) ?></dd>
        <dt>Admin Remarks</dt><dd><?= e($request['admin_remarks'] ?: 'No remarks') ?></dd>
    </dl>

    <?php if ($request['status'] === 'Pending'): ?>
        <div class="form-actions top-space">
            <form method="post" action="<?= e(path('/admin/registration-requests/approve')) ?>" onsubmit="return confirm('Approve this tutor registration?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($request['id']) ?>">
                <button class="button primary" type="submit">Approve</button>
            </form>
            <form method="post" action="<?= e(path('/admin/registration-requests/reject')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($request['id']) ?>">
                <input name="admin_remarks" placeholder="Optional rejection remarks">
                <button class="button danger" type="submit">Reject</button>
            </form>
        </div>
    <?php endif; ?>
</section>
