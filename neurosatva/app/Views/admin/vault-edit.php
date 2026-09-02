<div class="panel module-editor-card">
    <form method="post" enctype="multipart/form-data" action="<?= e(path('/admin/vault/update')) ?>" id="module-form" class="stack" style="gap: 28px;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($module['id']) ?>">
        <input type="hidden" name="_method" value="PUT">

        <!-- Page Header -->
        <div style="margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,.12); padding-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 800; margin: 0; color: #fff;">Edit Module: <?= e($module['name']) ?></h2>
                    <p style="margin: 4px 0 0; color: rgba(255,255,255,.65); font-size: 14px;">Modify module identity, master video file, audio assets, config JSON, and timeline scenes.</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="<?= e(path('/admin/vault')) ?>" class="button ghost">Back to Vault</a>
                    <button type="submit" class="button primary">Update Module (v<?= e($module['version'] + 1) ?>)</button>
                </div>
            </div>
        </div>

        <!-- STEP 1: Basic Module Identity -->
        <div class="editor-section">
            <div class="editor-section-header">
                <span class="step-badge">1</span>
                <h3>Module Identity</h3>
            </div>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="stack" style="gap: 16px;">
                    <label class="stack">
                        <span>Module Name *</span>
                        <input type="text" name="name" required value="<?= e($module['name']) ?>">
                    </label>
                    <label class="stack">
                        <span>Description</span>
                        <textarea name="description" rows="3"><?= e($module['description'] ?? '') ?></textarea>
                    </label>
                </div>
                <div>
                    <label class="stack" style="margin-bottom: 8px;">Thumbnail Image</label>
                    <?php if (!empty($module['thumbnail'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?= e(path('/storage-serve/modules?folder=' . urlencode($module['folder_name']) . '&file=' . urlencode($module['thumbnail']))) ?>" style="max-height: 80px; border-radius: 8px; border: 1px solid rgba(255,255,255,.18);">
                        </div>
                    <?php endif; ?>
                    <div class="drop-zone compact-dropzone" id="thumb-drop-zone" data-allowed-exts="png,jpg,jpeg,webp" data-allowed-label=".png, .jpg, .jpeg, .webp">
                        <span class="drop-text">📸 Drop to replace thumbnail</span>
                        <input type="file" name="thumbnail" accept=".png,.jpg,.jpeg,.webp" style="display: none;" id="thumb-input">
                    </div>
                    <div id="thumb-preview" style="margin-top: 8px;"></div>
                </div>
            </div>
        </div>

        <!-- STEP 2: Required Media Files & Configuration -->
        <div class="editor-section">
            <div class="editor-section-header">
                <span class="step-badge">2</span>
                <h3>Required Files &amp; Configuration</h3>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- 1. Master Video File -->
                <div class="media-box">
                    <h4 style="margin: 0 0 6px; font-size: 14px; color: #38bdf8; font-weight: 700;">1. Master Video File</h4>
                    <?php if (!empty($module['video_name'])): ?>
                        <div style="margin-bottom: 8px; font-size: 12px; color: rgba(255,255,255,.8);">
                            <strong style="color: #14D8C4;">Current:</strong> <?= e($module['video_name']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="drop-zone" id="video-drop-zone" data-allowed-exts="mp4,mov,webm,mkv" data-allowed-label=".mp4, .mov, .webm, .mkv">
                        <span class="drop-text">Drag &amp; drop to replace video (.mp4, .mov)</span>
                        <input type="file" name="video" accept=".mp4,.mov,.webm,.mkv" style="display: none;" id="video-input">
                    </div>
                    <div id="video-filename" style="margin-top: 8px; font-size: 12px; color: rgba(255,255,255,.8); font-weight: 500;"></div>
                </div>

                <!-- 3. Config JSON File -->
                <div class="media-box">
                    <h4 style="margin: 0 0 6px; font-size: 14px; color: #14D8C4; font-weight: 700;">3. Config JSON File</h4>
                    <p style="font-size: 12px; color: rgba(255,255,255,.65); margin: 0 0 12px;">Module configuration file (defines timeline, audio, lighting &amp; RGB).</p>
                    <div class="drop-zone" id="config-json-drop-zone" data-allowed-exts="json" data-allowed-label=".json">
                        <span class="drop-text">Drag &amp; drop config.json or click to browse</span>
                        <input type="file" name="config_json" accept=".json" style="display: none;" id="config-json-input">
                    </div>
                    <div id="config-json-banner" style="display: none; margin-top: 10px; padding: 10px; background: rgba(20, 184, 166, .2); border-radius: 8px; color: #5eead4; font-size: 12px;"></div>
                </div>

                <!-- 2. Audio Track Assets -->
                <div class="media-box" style="grid-column: 1 / -1;">
                    <h4 style="margin: 0 0 6px; font-size: 14px; color: #a78bfa; font-weight: 700;">2. Audio Track Assets</h4>
                    
                    <?php
                    $audioFiles = [];
                    $folder = Module::storagePath($module['folder_name']);
                    if (is_dir($folder)) {
                        foreach (glob($folder . '/*.{mp3,wav,ogg,m4a,aac}', GLOB_BRACE) as $file) {
                            $audioFiles[] = basename($file);
                        }
                    }
                    ?>

                    <?php if (!empty($audioFiles)): ?>
                        <div style="margin-bottom: 14px;">
                            <p style="font-weight: 600; margin: 0 0 8px; color: rgba(255,255,255,.72); font-size: 12.5px;">Existing Audio Files:</p>
                            <div class="stack" style="gap: 8px;">
                                <?php foreach ($audioFiles as $af): ?>
                                    <div class="audio-file-row">
                                        <span style="flex: 1;"><?= e($af) ?></span>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <button type="button" class="button ghost small" onclick="new Audio('<?= e(path('/storage-serve/modules?folder=' . urlencode($module['folder_name']) . '&file=' . urlencode($af))) ?>').play()">▶ Play</button>
                                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #fca5a5; cursor: pointer;">
                                                <input type="checkbox" name="delete_audio[]" value="<?= e($af) ?>"> Delete
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="drop-zone" id="audio-drop-zone" data-allowed-exts="mp3,wav,ogg,m4a,aac" data-allowed-label=".mp3, .wav, .ogg, .m4a, .aac">
                        <span class="drop-text">Drag &amp; drop additional audio files or click to browse</span>
                        <input type="file" name="audio[]" accept=".mp3,.wav,.ogg,.m4a,.aac" multiple style="display: none;" id="audio-input">
                    </div>
                    <div id="audio-file-list" class="audio-file-list" style="margin-top: 12px;"></div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Timeline Scene Configuration -->
        <div class="editor-section">
            <div class="editor-section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="step-badge">3</span>
                    <h3>Timeline Scene Configuration</h3>
                </div>
                <button type="button" id="add-scene-btn" class="button secondary small">+ Add Scene</button>
            </div>
            <p style="font-size: 13px; color: rgba(255,255,255,.65); margin: -4px 0 18px;">
                Configure scene duration, state name, audio track, volume, frequency, modulation, brightness, CCT, and RGB color.
            </p>

            <div id="scene-list" class="stack" style="gap: 14px;">
                <?php
                $timeline = $config['timeline'] ?? [];
                if (empty($timeline)) {
                    $timeline[] = ['duration' => 60, 'state' => 'focus', 'audio' => '', 'audio_volume' => 1.0, 'frequency' => '', 'modulation' => 'None', 'brightness' => 50, 'cct' => 50, 'rgb' => [0,0,0]];
                }
                ?>
                <?php foreach ($timeline as $i => $scene): ?>
                    <div class="scene-row" data-index="<?= $i ?>">
                        <div class="scene-header">
                            <span class="drag-handle" style="cursor: grab; opacity: .5;">⠿</span>
                            <span class="scene-number"><?= $i + 1 ?></span>
                            <span class="scene-summary">Scene <?= $i + 1 ?> — <?= e($scene['duration']) ?>s — <?= e($scene['state']) ?></span>
                            <button type="button" class="button ghost small duplicate-scene">Copy</button>
                            <button type="button" class="button danger small delete-scene">Delete</button>
                        </div>
                        <div class="scene-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 18px; border-top: 1px solid rgba(255,255,255,.1);">
                            <div class="stack" style="gap: 6px;">
                                <label>Duration (seconds)</label>
                                <input type="number" name="scenes[<?= $i ?>][duration]" min="1" value="<?= e($scene['duration']) ?>" class="scene-duration">
                            </div>
                            <div class="stack" style="gap: 6px;">
                                <label>State Name</label>
                                <input type="text" name="scenes[<?= $i ?>][state]" value="<?= e($scene['state']) ?>" class="scene-state" placeholder="e.g. focus, rest, energize">
                            </div>
                            <div class="stack" style="gap: 6px;">
                                <label>Audio File</label>
                                <select name="scenes[<?= $i ?>][audio]" class="scene-audio-select" data-selected="<?= e($scene['audio'] ?? '') ?>">
                                    <option value="">-- None --</option>
                                    <?php foreach ($audioFiles as $af): ?>
                                        <option value="<?= e($af) ?>" <?= ($scene['audio'] ?? '') === $af ? 'selected' : '' ?>><?= e($af) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="stack" style="gap: 6px;">
                                <label>Audio Volume (<span class="vol-val"><?= e($scene['audio_volume'] ?? 1.0) ?></span>)</label>
                                <input type="range" name="scenes[<?= $i ?>][audio_volume]" min="0" max="1" step="0.01" value="<?= e($scene['audio_volume'] ?? 1.0) ?>" oninput="this.previousElementSibling.querySelector('.vol-val').textContent = this.value">
                            </div>
                            <div class="stack" style="gap: 6px;">
                                <label>Frequency</label>
                                <input type="text" name="scenes[<?= $i ?>][frequency]" value="<?= e($scene['frequency'] ?? '') ?>" placeholder="e.g. 6 Hz">
                            </div>
                            <div class="stack" style="gap: 6px;">
                                <label>Audio Modulation</label>
                                <select name="scenes[<?= $i ?>][modulation]">
                                    <?php $modVal = $scene['modulation'] ?? 'None'; ?>
                                    <option value="None" <?= in_array($modVal, ['None', '']) ? 'selected' : '' ?>>None</option>
                                    <option value="Amplitude Modulation (AM)" <?= in_array($modVal, ['Amplitude Modulation (AM)', 'AM']) ? 'selected' : '' ?>>Amplitude Modulation (AM)</option>
                                    <option value="Isochronic Pulse" <?= in_array($modVal, ['Isochronic Pulse', 'Isochronic']) ? 'selected' : '' ?>>Isochronic Pulse</option>
                                    <option value="Monaural Beat" <?= in_array($modVal, ['Monaural Beat', 'Monaural']) ? 'selected' : '' ?>>Monaural Beat</option>
                                    <option value="Binaural Beat" <?= in_array($modVal, ['Binaural Beat', 'Binaural']) ? 'selected' : '' ?>>Binaural Beat</option>
                                    <option value="Tremolo / Slow AM" <?= in_array($modVal, ['Tremolo / Slow AM', 'Tremolo']) ? 'selected' : '' ?>>Tremolo / Slow AM</option>
                                </select>
                            </div>
                            <div class="stack" style="gap: 6px;">
                                <label>Brightness (0–100)</label>
                                <input type="range" name="scenes[<?= $i ?>][brightness]" min="0" max="100" value="<?= e($scene['brightness'] ?? 50) ?>">
                            </div>
                            <div class="stack" style="gap: 6px;">
                                <label>CCT (0–100)</label>
                                <input type="range" name="scenes[<?= $i ?>][cct]" min="0" max="100" value="<?= e($scene['cct'] ?? 50) ?>">
                            </div>
                            <div class="stack" style="gap: 6px; grid-column: span 2;">
                                <label>RGB Color [r, g, b]</label>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <?php $rgb = [$scene['rgb_r'] ?? ($scene['rgb'][0] ?? 0), $scene['rgb_g'] ?? ($scene['rgb'][1] ?? 0), $scene['rgb_b'] ?? ($scene['rgb'][2] ?? 0)]; ?>
                                    <input type="number" name="scenes[<?= $i ?>][rgb_r]" min="0" max="255" value="<?= e($rgb[0]) ?>" placeholder="R" style="width: 80px;" class="rgb-r">
                                    <input type="number" name="scenes[<?= $i ?>][rgb_g]" min="0" max="255" value="<?= e($rgb[1]) ?>" placeholder="G" style="width: 80px;" class="rgb-g">
                                    <input type="number" name="scenes[<?= $i ?>][rgb_b]" min="0" max="255" value="<?= e($rgb[2]) ?>" placeholder="B" style="width: 80px;" class="rgb-b">
                                    <div class="color-swatch" style="width: 36px; height: 36px; border-radius: 8px; background: rgb(<?= (int)$rgb[0] ?>,<?= (int)$rgb[1] ?>,<?= (int)$rgb[2] ?>); border: 1px solid rgba(255,255,255,.2); flex-shrink: 0;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.12);">
            <a href="<?= e(path('/admin/vault')) ?>" class="button ghost">Back to Vault</a>
            <button type="submit" class="button primary">Update Module (v<?= e($module['version'] + 1) ?>)</button>
        </div>
    </form>
</div>

<script src="<?= e(path('/assets/js/runtime.js?v=' . asset_version('assets/js/runtime.js'))) ?>"></script>
<script src="<?= e(path('/assets/js/app.js?v=' . asset_version('assets/js/app.js'))) ?>"></script>
