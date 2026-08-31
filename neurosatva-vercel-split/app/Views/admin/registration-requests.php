<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Tutor Verification</h2>
            <p>Review tutor registration forms and approve or reject tutor access.</p>
        </div>
    </div>

    <form class="filters" method="get">
        <input name="search" placeholder="Search name, email, or school" value="<?= e(input('search')) ?>" data-table-search>
        <select name="status">
            <option value="">All statuses</option>
            <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
            <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <button class="button secondary" type="submit">Filter</button>
    </form>

    <div class="table-wrap">
        <table data-searchable-table>
            <thead><tr><th>Application ID</th><th>Name</th><th>Email</th><th>Phone</th><th>School</th><th>Gender</th><th>Registration Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <?php $modalId = 'request-modal-' . (int) $request['id']; ?>
                <tr>
                    <td><strong>NS-TUTOR-<?= e(str_pad((string) $request['id'], 5, '0', STR_PAD_LEFT)) ?></strong></td>
                    <td><?= e($request['full_name']) ?></td>
                    <td><?= e($request['email']) ?></td>
                    <td><?= e($request['phone'] ?? 'Not provided') ?></td>
                    <td><?= e($request['school_name']) ?></td>
                    <td><?= e($request['gender']) ?></td>
                    <td><?= e(date('d M Y', strtotime($request['created_at']))) ?></td>
                    <td><?= status_badge($request['status']) ?></td>
                    <td class="actions">
                        <button class="button small" type="button" data-open-modal="<?= e($modalId) ?>">View</button>
                        <?php if ($request['status'] === 'Pending'): ?>
                            <form method="post" action="<?= e(path('/admin/registration-requests/approve')) ?>" onsubmit="return confirm('Are you sure you want to approve this tutor?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($request['id']) ?>">
                                <button class="button small secondary" type="submit">Approve</button>
                            </form>
                            <form method="post" action="<?= e(path('/admin/registration-requests/reject')) ?>" onsubmit="const remarks = prompt('Enter rejection reason'); if (remarks === null) return false; this.admin_remarks.value = remarks; return true;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($request['id']) ?>">
                                <input type="hidden" name="admin_remarks" value="">
                                <button class="button small danger" type="submit">Reject</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?>
                <tr><td colspan="9" class="empty">No registration requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php foreach ($requests as $request): ?>
    <?php $modalId = 'request-modal-' . (int) $request['id']; ?>
    <div class="glass-modal" id="<?= e($modalId) ?>" aria-hidden="true">
        <div class="glass-modal-card" role="dialog" aria-modal="true" aria-labelledby="<?= e($modalId) ?>-title">
            <div class="panel-head">
                <div>
                    <h2 id="<?= e($modalId) ?>-title">Tutor Application</h2>
                    <p><?= e($request['full_name']) ?></p>
                </div>
                <?= status_badge($request['status']) ?>
            </div>
            <dl class="profile-list">
                <dt>Application ID</dt><dd>NS-TUTOR-<?= e(str_pad((string) $request['id'], 5, '0', STR_PAD_LEFT)) ?></dd>
                <dt>Full Name</dt><dd><?= e($request['full_name']) ?></dd>
                <dt>Email Address</dt><dd><?= e($request['email']) ?></dd>
                <dt>Phone Number</dt><dd><?= e($request['phone'] ?? 'Not provided') ?></dd>
                <dt>School Name</dt><dd><?= e($request['school_name']) ?></dd>
                <dt>Gender</dt><dd><?= e($request['gender']) ?></dd>
                <dt>Registration Date</dt><dd><?= e(date('d M Y h:i A', strtotime($request['created_at']))) ?></dd>
                <dt>Current Status</dt><dd><?= status_badge($request['status']) ?></dd>
            </dl>
            <div class="form-actions top-space">
                <?php if ($request['status'] === 'Pending'): ?>
                    <form method="post" action="<?= e(path('/admin/registration-requests/approve')) ?>" onsubmit="return confirm('Are you sure you want to approve this tutor?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($request['id']) ?>">
                        <button class="button primary" type="submit">Approve Tutor</button>
                    </form>
                    <form method="post" action="<?= e(path('/admin/registration-requests/reject')) ?>" onsubmit="const remarks = prompt('Enter rejection reason'); if (remarks === null) return false; this.admin_remarks.value = remarks; return true;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($request['id']) ?>">
                        <input type="hidden" name="admin_remarks" value="">
                        <button class="button danger" type="submit">Reject Tutor</button>
                    </form>
                <?php endif; ?>
                <button class="button ghost" type="button" data-close-modal>Close</button>
            </div>
        </div>
    </div>
<?php endforeach; ?>
