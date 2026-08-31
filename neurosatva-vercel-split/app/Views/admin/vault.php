<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Digital Vault</h2>
            <p>Manage your sensory modules and synchronize content with lighting controllers.</p>
        </div>
        <a href="<?= e(path('/admin/vault/create')) ?>" class="button primary">Create Module</a>
    </div>

    <!-- Toolbar -->
    <div style="margin-bottom: 24px;">
        <form method="get" action="<?= e(path('/admin/vault')) ?>" class="grid-form" style="grid-template-columns: 1fr 1fr auto; max-width: 600px; gap: 12px; align-items: end;">
            <div class="form-group stack">
                <label>Search</label>
                <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search modules...">
            </div>
            <div class="form-group stack">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="archived" <?= ($status ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div>
                <button type="submit" class="button ghost">Filter</button>
            </div>
        </form>
    </div>

    <!-- Stats row -->
    <?php
    $total = count($modules);
    $active = count(array_filter($modules, fn($m) => $m['status'] === 'active'));
    $archived = count(array_filter($modules, fn($m) => $m['status'] === 'archived'));
    ?>
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-value"><?= e($total) ?></div>
            <div class="stat-label">Total Modules</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= e($active) ?></div>
            <div class="stat-label">Active Modules</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= e($archived) ?></div>
            <div class="stat-label">Archived Modules</div>
        </div>
    </div>

    <!-- Module Grid -->
    <?php if (empty($modules)): ?>
        <div class="empty-state">
            <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
            <h3>No modules found</h3>
            <p>Get started by creating your first sensory module.</p>
            <a href="<?= e(path('/admin/vault/create')) ?>" class="button primary" style="margin-top: 16px;">Create your first module</a>
        </div>
    <?php else: ?>
        <div class="vault-grid">
            <?php foreach ($modules as $module): ?>
                <div class="module-card">
                    <div class="module-thumb">
                        <?php if (!empty($module['thumbnail'])): ?>
                            <img src="<?= e(path('/storage-serve/modules?folder=' . urlencode($module['folder_name']) . '&file=' . urlencode($module['thumbnail']))) ?>" alt="Thumbnail">
                        <?php else: ?>
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 48px; background: var(--bg-alt);">🎬</div>
                        <?php endif; ?>
                        <div class="module-version-badge">v<?= e($module['version']) ?></div>
                    </div>
                    <div class="module-card-body stack">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <h3 style="margin: 0; font-size: 18px;"><?= e($module['name']) ?></h3>
                            <span class="badge <?= $module['status'] === 'active' ? 'active' : 'archived' ?>"><?= e(ucfirst($module['status'])) ?></span>
                        </div>
                        <div class="module-card-meta stack" style="font-size: 13px; color: var(--muted); margin-bottom: 16px; gap: 4px;">
                            <div><strong>Video:</strong> <?= e($module['video_name'] ?: 'None') ?></div>
                            <div><strong>Audio Files:</strong> <?= e($module['audio_count']) ?></div>
                            <div><strong>Scenes:</strong> <?= e($module['scene_count']) ?></div>
                            <div><strong>Created:</strong> <?= date('M j, Y', strtotime($module['created_at'])) ?></div>
                        </div>
                        <div class="module-card-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="<?= e(path('/admin/vault/edit?id=' . $module['id'])) ?>" class="button ghost small">Edit</a>
                            <button type="button" class="button primary small test-module-btn" data-test-module="<?= e($module['id']) ?>" data-module-name="<?= e($module['name']) ?>">Test</button>
                            <form method="post" action="<?= e(path('/admin/vault/delete')) ?>" onsubmit="return confirm('Are you sure you want to delete this module? Active assignments will be affected.');" style="display: inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($module['id']) ?>">
                                <input type="hidden" name="confirm_delete" value="1">
                                <button type="submit" class="button danger small">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Test Module Modal -->
<div class="modal-backdrop" id="test-modal" style="display: none;">
    <div class="modal">
        <div class="panel-head">
            <h3>Test Module: <span id="test-modal-name"></span></h3>
        </div>
        <div class="stack" style="padding: 24px;">
            <p>Enter the IP address of an ESP32 lighting controller on your network to run a test sync.</p>
            <div class="form-group stack">
                <label>ESP32 / WLED IP Address</label>
                <input type="text" id="test-ip-input" placeholder="192.168.1.25" pattern="^(\d{1,3}\.){3}\d{1,3}$" autocomplete="off">
            </div>
            <div id="test-error-display" style="color: #ff8080; display: none; font-size: 14px; margin-top: 8px;"></div>
            <input type="hidden" id="test-module-id">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px; padding: 16px 24px; border-top: 1px solid var(--border);">
            <button type="button" class="button ghost" id="test-cancel-btn">Cancel</button>
            <button type="button" class="button primary" id="test-connect-btn">Connect & Test</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('test-modal');
    const nameDisplay = document.getElementById('test-modal-name');
    const idInput = document.getElementById('test-module-id');
    const ipInput = document.getElementById('test-ip-input');
    const cancelBtn = document.getElementById('test-cancel-btn');
    const connectBtn = document.getElementById('test-connect-btn');
    const errorDisplay = document.getElementById('test-error-display');

    document.querySelectorAll('.test-module-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            idInput.value = this.dataset.testModule;
            nameDisplay.textContent = this.dataset.moduleName;
            errorDisplay.style.display = 'none';
            ipInput.value = '';
            modal.style.display = 'flex';
        });
    });

    cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    connectBtn.addEventListener('click', () => {
        const ip = ipInput.value.trim();
        const valid = /^(\d{1,3}\.){3}\d{1,3}$/.test(ip) && ip.split('.').every(n => parseInt(n) >= 0 && parseInt(n) <= 255);
        if (!valid) {
            errorDisplay.textContent = 'Please enter a valid IP address.';
            errorDisplay.style.display = 'block';
            return;
        }
        
        window.location.href = '<?= e(path('/admin/vault/test')) ?>?id=' + encodeURIComponent(idInput.value) + '&ip=' + encodeURIComponent(ip);
    });
});
</script>
