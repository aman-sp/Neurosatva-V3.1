<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Module Assignments</h2>
            <p>Manage access and view usage for sensory modules.</p>
        </div>
        <a href="<?= e(path('/admin/assign')) ?>" class="button primary">New Assignment</a>
    </div>

    <!-- Filter bar -->
    <div style="margin-bottom: 24px;">
        <form method="get" action="<?= e(path('/admin/assignments')) ?>" class="grid-form" style="grid-template-columns: 1fr 1fr auto; max-width: 600px; gap: 12px; align-items: end;">
            <div class="form-group stack">
                <label>Filter by Tutor</label>
                <select name="tutor_id">
                    <option value="">All Tutors</option>
                    <?php foreach ($tutors as $t): ?>
                        <option value="<?= e($t['id']) ?>" <?= ($_GET['tutor_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group stack">
                <label>Filter by Module</label>
                <select name="module_id">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= e($m['id']) ?>" <?= ($_GET['module_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="button ghost">Filter</button>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <?php
    $total = count($assignments);
    $active = count(array_filter($assignments, fn($a) => $a['status'] === 'active'));
    $expired = count(array_filter($assignments, fn($a) => $a['status'] === 'expired'));
    $revoked = count(array_filter($assignments, fn($a) => $a['status'] === 'revoked'));
    ?>
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-value"><?= e($total) ?></div>
            <div class="stat-label">Total Assignments</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= e($active) ?></div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= e($expired) ?></div>
            <div class="stat-label">Expired</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= e($revoked) ?></div>
            <div class="stat-label">Revoked</div>
        </div>
    </div>

    <!-- Table -->
    <?php if (empty($assignments)): ?>
        <div class="empty-state">
            <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
            <h3>No assignments found</h3>
            <p>Assign modules to tutors to see them here.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tutor</th>
                        <th>Module</th>
                        <th>ESP32 IP</th>
                        <th>Plays Remaining</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Assigned</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td>
                                <div><strong><?= e($a['tutor_name']) ?></strong></div>
                                <div style="font-size: 12px; color: var(--muted);"><?= e($a['tutor_email']) ?></div>
                            </td>
                            <td><?= e($a['module_name']) ?> <span class="badge" style="font-size: 10px;">v<?= e($a['module_version']) ?></span></td>
                            <td><code style="background: var(--bg-alt); padding: 2px 6px; border-radius: 4px;"><?= e($a['esp32_ip']) ?></code></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span><?= e($a['remaining_plays']) ?> / <?= e($a['total_plays']) ?></span>
                                    <?php if ($a['status'] === 'active'): ?>
                                        <?php $pct = $a['total_plays'] > 0 ? ($a['remaining_plays'] / $a['total_plays']) * 100 : 0; ?>
                                        <div class="plays-bar" style="width: 60px; height: 6px; background: var(--bg-alt); border-radius: 3px; overflow: hidden;">
                                            <div class="plays-bar-fill <?= $pct < 25 ? 'low' : '' ?>" style="width: <?= $pct ?>%; height: 100%; background: <?= $pct < 25 ? 'var(--danger)' : 'var(--primary)' ?>;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= $a['expiry_date'] ? date('M j, Y', strtotime($a['expiry_date'])) : 'No expiry' ?></td>
                            <td>
                                <span class="badge <?= $a['status'] ?>"><?= e(ucfirst($a['status'])) ?></span>
                            </td>
                            <td><?= date('M j, Y', strtotime($a['assigned_at'])) ?></td>
                            <td>
                                <?php if ($a['status'] === 'active'): ?>
                                    <form method="post" action="<?= e(path('/admin/assignments/revoke')) ?>" onsubmit="return confirm('Revoke this assignment? The tutor will no longer be able to play this module.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                        <button type="submit" class="button danger small">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
