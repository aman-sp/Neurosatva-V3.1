<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Digital Vault</h2>
            <p>Manage synchronized video, audio, lighting, and timeline packages.</p>
        </div>
        <a class="button primary" href="<?= e(path('/admin/modules/create')) ?>">Create Module</a>
    </div>

    <form class="filters" method="get">
        <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search modules">
        <select name="sort">
            <option value="">Newest first</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
            <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option>
            <option value="scenes" <?= $sort === 'scenes' ? 'selected' : '' ?>>Most scenes</option>
        </select>
        <button class="button ghost" type="submit">Apply</button>
    </form>

    <?php if (!$modules): ?>
        <p class="empty">No modules have been created yet.</p>
    <?php endif; ?>

    <div class="module-grid">
        <?php foreach ($modules as $module): ?>
            <article class="module-card">
                <div class="module-thumb">
                    <?php if ($module['thumbnail_path']): ?>
                        <img src="<?= e(path('/modules/file?module_id=' . $module['id'] . '&file=' . rawurlencode(basename($module['thumbnail_path'])))) ?>" alt="">
                    <?php else: ?>
                        <span><?= e(substr($module['module_name'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="module-card-body">
                    <div class="module-title-row">
                        <h3><?= e($module['module_name']) ?></h3>
                        <?= status_badge($module['status']) ?>
                    </div>
                    <p><?= e($module['video_name']) ?></p>
                    <dl class="module-meta">
                        <dt>Audio</dt><dd><?= e($module['audio_count']) ?></dd>
                        <dt>Scenes</dt><dd><?= e($module['scene_count']) ?></dd>
                        <dt>Created</dt><dd><?= e(date('d M Y', strtotime($module['created_at']))) ?></dd>
                        <dt>Version</dt><dd><?= e($module['version']) ?></dd>
                    </dl>
                    <div class="actions wrap">
                        <a class="button small ghost" href="<?= e(path('/admin/modules/view?id=' . $module['id'])) ?>">View</a>
                        <a class="button small secondary" href="<?= e(path('/admin/modules/edit?id=' . $module['id'])) ?>">Edit</a>
                        <a class="button small primary" href="<?= e(path('/admin/modules/test?id=' . $module['id'])) ?>">Test</a>
                        <form method="post" action="<?= e(path('/admin/modules/delete')) ?>" data-confirm-delete>
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($module['id']) ?>">
                            <?php if ((int) $module['active_assignments'] > 0): ?>
                                <input type="hidden" name="confirm_assigned_delete" value="1">
                            <?php endif; ?>
                            <button class="button small danger" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
