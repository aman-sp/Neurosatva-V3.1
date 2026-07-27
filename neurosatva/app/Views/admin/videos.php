<section class="panel accent-panel">
    <h2>Verification workflow</h2>
    <ol class="steps">
        <li>Tutor submits a video title, description, and folder link.</li>
        <li>Admin manually checks the link, content, and quality.</li>
        <li>Admin marks verified and stores a secure video path.</li>
        <li>Only verified videos appear in the tutor dashboard.</li>
    </ol>
</section>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Video Verification</h2>
            <p>Filter and update received video records.</p>
        </div>
    </div>
    <form class="filters" method="get">
        <select name="status">
            <option value="">All statuses</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="verified" <?= $status === 'verified' ? 'selected' : '' ?>>Verified</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <select name="tutor_id">
            <option value="">All tutors</option>
            <?php foreach ($tutors as $tutor): ?>
                <option value="<?= e($tutor['id']) ?>" <?= (string) $tutorId === (string) $tutor['id'] ? 'selected' : '' ?>><?= e($tutor['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button secondary" type="submit">Filter</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tutor</th><th>Received</th><th>Status</th><th>Verify</th></tr></thead>
            <tbody>
            <?php foreach ($videos as $video): ?>
                <tr>
                    <td><?= e($video['tutor_name']) ?></td>
                    <td><?= e(date('d M Y', strtotime($video['received_at']))) ?></td>
                    <td><?= status_badge($video['status']) ?></td>
                    <td>
                        <form class="inline-form" method="post" action="<?= e(path('/admin/videos/verify')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($video['id']) ?>">
                            <select name="status">
                                <option value="pending" <?= $video['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="verified" <?= $video['status'] === 'verified' ? 'selected' : '' ?>>Verified</option>
                                <option value="rejected" <?= $video['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                            <input name="storage_path" value="<?= e($video['storage_path']) ?>" placeholder="Paste link here">
                            <button class="button secondary small" type="button" data-open-pasted-link>Open Link</button>
                            <input name="admin_remarks" value="<?= e($video['admin_remarks']) ?>" placeholder="Remarks">
                            <button class="button small" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
