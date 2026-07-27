<?php
$isEdit = (bool) $module;
$timeline = $config['timeline'] ?? [];
$action = $isEdit ? '/admin/modules/update' : '/admin/modules';
?>
<form class="panel module-builder" method="post" action="<?= e(path($action)) ?>" enctype="multipart/form-data" data-module-builder>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($module['id']) ?>"><?php endif; ?>
    <div class="panel-head">
        <div>
            <h2><?= e($isEdit ? 'Edit Module' : 'Create Module') ?></h2>
            <p>The saved config keeps the required NeuroSattva JSON structure and uses scene durations for timing.</p>
        </div>
        <button class="button primary" type="submit">Save Module</button>
    </div>

    <div class="grid-form">
        <label>Module Name
            <input name="module_name" required value="<?= e($module['module_name'] ?? '') ?>">
        </label>
        <label>Version
            <input name="version" value="<?= e($module['version'] ?? '1.0') ?>">
        </label>
        <label class="span-2">Description
            <textarea name="description" rows="3"><?= e($module['description'] ?? '') ?></textarea>
        </label>
        <label>Status
            <select name="status">
                <?php foreach (['active', 'draft', 'archived'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($module['status'] ?? 'active') === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Thumbnail (.png)
            <input type="file" name="thumbnail" accept=".png,image/png">
        </label>
        <label class="span-2">Video Upload (.mov or .mp4)
            <input type="file" name="video" accept=".mov,.mp4,video/mp4,video/quicktime" <?= $isEdit ? '' : 'required' ?>>
            <?php if ($isEdit): ?><span class="hint">Current: <?= e($module['video_name']) ?></span><?php endif; ?>
        </label>
    </div>

    <section class="builder-section">
        <div class="section-title">
            <h3>Audio Files</h3>
            <label class="button ghost file-button">Upload MP3s<input type="file" name="audio_files[]" accept=".mp3,audio/mpeg" multiple data-audio-upload></label>
        </div>
        <div class="audio-list" data-audio-list>
            <?php foreach ($audioFiles as $audio): ?>
                <div class="audio-row" data-audio-name="<?= e($audio) ?>">
                    <strong><?= e($audio) ?></strong>
                    <audio controls src="<?= e(path('/modules/file?module_id=' . ($module['id'] ?? 0) . '&file=' . rawurlencode($audio))) ?>"></audio>
                    <label>Rename <input name="rename_audio_to[]" value="<?= e($audio) ?>"></label>
                    <input type="hidden" name="rename_audio_from[]" value="<?= e($audio) ?>">
                    <label class="check-row"><input type="checkbox" name="delete_audio[]" value="<?= e($audio) ?>"><span>Delete</span></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="audio-list" data-pending-audio-list></div>
    </section>

    <section class="builder-section">
        <div class="section-title">
            <h3>Timeline Builder</h3>
            <button class="button secondary" type="button" data-add-scene>Add Scene</button>
        </div>
        <div class="scene-list" data-scene-list>
            <?php foreach ($timeline ?: [[]] as $scene): ?>
                <?php require dirname(__DIR__) . '/modules/scene-row.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
</form>
