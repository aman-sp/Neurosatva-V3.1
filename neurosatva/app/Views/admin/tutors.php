<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Manage Tutors</h2>
            <p>Edit, activate, deactivate, or delete tutor accounts.</p>
        </div>
    </div>

    <form class="filters" method="get">
        <input name="search" placeholder="Search tutor, email, or phone" value="<?= e($search) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="deactivated" <?= $status === 'deactivated' ? 'selected' : '' ?>>Deactivated</option>
        </select>
        <button class="button secondary" type="submit">Filter</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>User ID</th><th>Tutor Name</th><th>Email</th><th>Personal Email</th><th>Phone</th><th>Verification Status</th><th>Verification Date</th><th>School</th><th>Account Status</th><th>Videos</th><th>Actions</th></tr></thead>
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
                    <td class="actions">
                        <a class="button small" href="<?= e(path('/admin/tutors/edit?id=' . $tutor['id'])) ?>">Edit</a>
                        <form method="post" action="<?= e(path('/admin/tutors/delete')) ?>" onsubmit="return confirm('Delete this tutor and related video records?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($tutor['id']) ?>">
                            <button class="button small danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
