<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Your Modules</h2>
            <p>Click Play to begin a synchronized sensory session in your classroom.</p>
        </div>
    </div>

    <?php if (empty($assignments)): ?>
        <div class="empty-state">
            <div style="font-size: 56px; margin-bottom: 16px; opacity: .7;">📦</div>
            <h3>No modules found</h3>
            <p>No modules have been assigned to you yet. Please contact your administrator.</p>
        </div>
    <?php else: ?>
        <div class="tutor-vault-grid">
            <?php foreach ($assignments as $a): ?>
                <div class="assignment-card <?= !$a['is_playable'] ? 'locked' : '' ?>">
                    <div class="assignment-thumb">
                        <?php if (!empty($a['thumbnail'])): ?>
                            <img src="<?= e(path('/storage-serve/modules?folder=' . urlencode($a['folder_name']) . '&file=' . urlencode($a['thumbnail']))) ?>" alt="Thumbnail">
                        <?php else: ?>
                            <div style="font-size: 48px; opacity: .4;">🧠</div>
                        <?php endif; ?>
                    </div>
                    <div class="assignment-body stack" style="padding: 20px; flex: 1; gap: 12px;">
                        <h3 class="assignment-title"><?= e($a['module_name']) ?></h3>
                        
                        <div style="font-size: 13.5px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: rgba(255,255,255,.8);">
                                <span><strong><?= e($a['remaining_plays']) ?></strong> / <?= e($a['total_plays']) ?> plays remaining</span>
                            </div>
                            <?php $pct = $a['total_plays'] > 0 ? ($a['remaining_plays'] / $a['total_plays']) * 100 : 0; ?>
                            <div class="plays-bar">
                                <div class="plays-bar-fill <?= $pct < 25 ? 'low' : '' ?> <?= $pct == 0 ? 'exhausted' : '' ?>" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>

                        <div style="font-size: 12.5px; color: rgba(255,255,255,.65); margin-top: 6px;" class="stack">
                            <div><strong style="color: rgba(255,255,255,.85);">Expiry:</strong> <?= $a['expiry_date'] ? date('M j, Y', strtotime($a['expiry_date'])) : 'No expiry' ?></div>
                            <div><strong style="color: rgba(255,255,255,.85);">Assigned:</strong> <?= date('M j, Y', strtotime($a['assigned_at'])) ?></div>
                            <div><strong style="color: rgba(255,255,255,.85);">Target ESP32 IP:</strong> <code style="background: rgba(255,255,255,.1); padding: 2px 6px; border-radius: 4px; color: #5eead4;"><?= e($a['esp32_ip']) ?></code></div>
                        </div>
                    </div>
                    <div class="assignment-footer">
                        <?php if ($a['is_playable']): ?>
                            <a href="<?= e(path('/tutor/vault/play?id=' . $a['id'])) ?>" class="button primary full" style="width: 100%; text-align: center;">▶ Play Module</a>
                        <?php elseif ($a['status'] === 'expired'): ?>
                            <button class="button ghost full" style="width: 100%; opacity: .6;" disabled>⏰ Assignment Expired</button>
                        <?php elseif ($a['remaining_plays'] <= 0): ?>
                            <button class="button ghost full" style="width: 100%; opacity: .6;" disabled>🔒 No Plays Remaining</button>
                        <?php elseif ($a['status'] === 'revoked'): ?>
                            <button class="button ghost full" style="width: 100%; opacity: .6;" disabled>❌ Assignment Revoked</button>
                        <?php else: ?>
                            <button class="button ghost full" style="width: 100%; opacity: .6;" disabled>🔒 Locked</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
