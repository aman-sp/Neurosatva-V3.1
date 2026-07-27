<section class="panel">
    <div class="panel-head">
        <div>
            <h2>My Verified Videos</h2>
            <p>These records are mapped only to your tutor account.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Video Title</th><th>Upload Date</th><th>Status</th><th>Admin Remarks</th><th>Video</th></tr></thead>
            <tbody>
            <?php foreach ($videos as $video): ?>
                <tr>
                    <td><?= e($video['title']) ?></td>
                    <td><?= e(date('d M Y', strtotime($video['verified_at'] ?? $video['updated_at']))) ?></td>
                    <td><?= status_badge($video['status']) ?></td>
                    <td><?= e($video['admin_remarks'] ?: 'No remarks') ?></td>
                    <td>
                        <?php if ($video['storage_path']): ?>
                            <a class="button small" href="<?= e($video['storage_path']) ?>" target="_blank" rel="noopener">Open</a>
                        <?php else: ?>
                            <span class="muted">Unavailable</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$videos): ?>
                <tr><td colspan="5" class="empty">No verified videos are available yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
