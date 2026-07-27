<section class="panel">
    <div class="panel-head">
        <div>
            <h2><?= e($module['module_name']) ?></h2>
            <p><?= e($module['description'] ?: 'No description added.') ?></p>
        </div>
        <div class="actions">
            <a class="button secondary" href="<?= e(path('/admin/modules/edit?id=' . $module['id'])) ?>">Edit</a>
            <a class="button primary" href="<?= e(path('/admin/modules/test?id=' . $module['id'])) ?>">Test</a>
        </div>
    </div>
    <video class="runtime-video" src="<?= e($payload['video_url']) ?>" controls></video>
    <pre class="config-preview"><?= e(json_encode($payload['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
</section>
