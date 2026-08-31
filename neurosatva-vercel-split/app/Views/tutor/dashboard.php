<section class="stats-grid compact">
    <article class="stat-card"><span>Verified Videos</span><strong><?= e($totalVerified) ?></strong></article>
    <article class="stat-card"><span>Submission Method</span><strong>Email</strong></article>
    <article class="stat-card"><span>Access</span><strong>Own Records</strong></article>
</section>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Recent verified videos</h2>
            <p>Only admin-verified videos assigned to your tutor ID appear here.</p>
        </div>
        <a class="button primary" href="<?= e(path('/tutor/instructions')) ?>">Upload Link</a>
    </div>
    <div class="video-grid">
        <?php foreach ($videos as $video): ?>
            <article class="video-card">
                <span><?= status_badge($video['status']) ?></span>
                <h3><?= e($video['title']) ?></h3>
                <p><?= e($video['admin_remarks'] ?: 'No admin remarks.') ?></p>
                <small>Uploaded <?= e(date('d M Y', strtotime($video['verified_at'] ?? $video['updated_at']))) ?></small>
                <?php if ($video['storage_path']): ?>
                    <a class="button small secondary" href="<?= e($video['storage_path']) ?>" target="_blank" rel="noopener">Open Video</a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$videos): ?>
            <p class="empty">No verified videos are available yet.</p>
        <?php endif; ?>
    </div>
</section>
