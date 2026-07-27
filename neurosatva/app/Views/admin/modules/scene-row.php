<div class="scene-row" data-scene>
    <div class="scene-head">
        <strong>Scene</strong>
        <div class="actions">
            <button class="button small ghost" type="button" data-duplicate-scene>Duplicate</button>
            <button class="button small ghost" type="button" data-move-scene="up">Up</button>
            <button class="button small ghost" type="button" data-move-scene="down">Down</button>
            <button class="button small danger" type="button" data-delete-scene>Delete</button>
        </div>
    </div>
    <div class="grid-form">
        <label>Duration
            <input type="number" min="1" name="scene_duration[]" value="<?= e($scene['duration'] ?? 15) ?>" required>
        </label>
        <label>State
            <input name="scene_state[]" value="<?= e($scene['state'] ?? 'focus') ?>" required>
        </label>
        <label>Audio
            <select name="scene_audio[]" data-scene-audio required>
                <?php foreach ($audioFiles as $audio): ?>
                    <option value="<?= e($audio) ?>" <?= ($scene['audio'] ?? '') === $audio ? 'selected' : '' ?>><?= e($audio) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Audio Volume
            <input type="number" min="0" max="1" step="0.01" name="scene_audio_volume[]" value="<?= e($scene['audio_volume'] ?? 0.8) ?>">
        </label>
        <label>Frequency
            <input name="scene_frequency[]" value="<?= e($scene['frequency'] ?? '6 Hz') ?>">
        </label>
        <label>Modulation
            <input name="scene_modulation[]" value="<?= e($scene['modulation'] ?? 'Binaural') ?>">
        </label>
        <label>Brightness
            <input type="number" min="0" max="255" name="scene_brightness[]" value="<?= e($scene['brightness'] ?? 100) ?>">
        </label>
        <label>CCT
            <input type="number" min="0" max="255" name="scene_cct[]" value="<?= e($scene['cct'] ?? 30) ?>">
        </label>
        <label>RGB Red
            <input type="number" min="0" max="255" name="scene_rgb_r[]" value="<?= e($scene['rgb'][0] ?? 120) ?>">
        </label>
        <label>RGB Green
            <input type="number" min="0" max="255" name="scene_rgb_g[]" value="<?= e($scene['rgb'][1] ?? 0) ?>">
        </label>
        <label>RGB Blue
            <input type="number" min="0" max="255" name="scene_rgb_b[]" value="<?= e($scene['rgb'][2] ?? 255) ?>">
        </label>
    </div>
</div>
