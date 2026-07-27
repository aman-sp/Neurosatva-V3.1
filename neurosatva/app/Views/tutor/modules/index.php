<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Tutor Digital Vault</h2>
            <p>Your assigned NeuroSattva modules are ready to play.</p>
        </div>
    </div>
    <?php if (!$assignments): ?><p class="empty">No modules have been assigned yet.</p><?php endif; ?>
    <div class="module-grid simple">
        <?php foreach ($assignments as $assignment): ?>
            <?php
            $expired = !empty($assignment['expiry_date']) && strtotime($assignment['expiry_date'] . ' 23:59:59') < time();
            $playable = (int) $assignment['remaining_plays'] > 0 && !$expired && $assignment['status'] === 'active';
            ?>
            <article class="module-card">
                <div class="module-thumb">
                    <?php if ($assignment['thumbnail_path']): ?><img src="<?= e(path('/modules/file?module_id=' . $assignment['module_id'] . '&file=' . rawurlencode(basename($assignment['thumbnail_path'])))) ?>" alt=""><?php else: ?><span><?= e(substr($assignment['module_name'], 0, 1)) ?></span><?php endif; ?>
                </div>
                <div class="module-card-body">
                    <h3><?= e($assignment['module_name']) ?></h3>
                    <dl class="module-meta">
                        <dt>Remaining Plays</dt><dd><?= e($assignment['remaining_plays']) ?></dd>
                        <dt>Assigned</dt><dd><?= e(date('d M Y', strtotime($assignment['assigned_at']))) ?></dd>
                        <dt>Expiry</dt><dd><?= e($assignment['expiry_date'] ?: 'No expiry') ?></dd>
                    </dl>
                    <?php if ($playable): ?>
                        <a class="button primary" href="<?= e(path('/tutor/modules/play?id=' . $assignment['id'])) ?>">Play</a>
                    <?php else: ?>
                        <button class="button ghost" type="button" disabled>Unavailable</button>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
