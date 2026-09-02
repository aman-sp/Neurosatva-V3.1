<div class="panel module-editor-card">
    <form method="post" enctype="multipart/form-data" action="<?= e(path('/admin/vault')) ?>" id="module-form" class="stack" style="gap: 28px;">
        <?= csrf_field() ?>

        <!-- Page Header -->
        <div style="margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,.12); padding-bottom: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 800; margin: 0; color: #fff;">Create New Module</h2>
                    <p style="margin: 4px 0 0; color: rgba(255,255,255,.65); font-size: 14px;">Design a synchronized sensory module with video, audio assets, and configuration.</p>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="<?= e(path('/admin/vault')) ?>" class="button ghost">Back to Vault</a>
                    <button type="submit" class="button primary">Save &amp; Publish Module</button>
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
                        <input type="text" name="name" required placeholder="e.g. Focus Session Alpha">
                    </label>
                    <label class="stack">
                        <span>Description</span>
                        <textarea name="description" rows="3" placeholder="Brief description of this sensory module..."></textarea>
                    </label>
                </div>
                <div>
                    <label class="stack" style="margin-bottom: 8px;">Thumbnail Image</label>
                    <div class="drop-zone compact-dropzone" id="thumb-drop-zone" data-allowed-exts="png,jpg,jpeg,webp" data-allowed-label=".png, .jpg, .jpeg, .webp">
                        <span class="drop-text">📸 Drop thumbnail (.jpg, .png)</span>
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
                    <p style="font-size: 12px; color: rgba(255,255,255,.65); margin: 0 0 12px;">Primary video clock file (.mp4 or .mov).</p>
                    <div class="drop-zone" id="video-drop-zone" data-allowed-exts="mp4,mov,webm,mkv" data-allowed-label=".mp4, .mov, .webm, .mkv">
                        <span class="drop-text">Drag &amp; drop video (.mp4, .mov) or click to browse</span>
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
                    <p style="font-size: 12px; color: rgba(255,255,255,.65); margin: 0 0 12px;">Upload n audio files (.mp3, .wav) referenced in timeline scenes.</p>
                    <div class="drop-zone" id="audio-drop-zone" data-allowed-exts="mp3,wav,ogg,m4a,aac" data-allowed-label=".mp3, .wav, .ogg, .m4a, .aac">
                        <span class="drop-text">Drag &amp; drop audio files or click to browse (.mp3, .wav)</span>
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
                <!-- Default Scene 1 -->
                <div class="scene-row" data-index="0">
                    <div class="scene-header">
                        <span class="drag-handle" style="cursor: grab; opacity: .5;">⠿</span>
                        <span class="scene-number">1</span>
                        <span class="scene-summary">Scene 1 — 60s — focus</span>
                        <button type="button" class="button ghost small duplicate-scene">Copy</button>
                        <button type="button" class="button danger small delete-scene">Delete</button>
                    </div>
                    <div class="scene-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 18px; border-top: 1px solid rgba(255,255,255,.1);">
                        <div class="stack" style="gap: 6px;">
                            <label>Duration (seconds)</label>
                            <input type="number" name="scenes[0][duration]" min="1" value="60" class="scene-duration">
                        </div>
                        <div class="stack" style="gap: 6px;">
                            <label>State Name</label>
                            <input type="text" name="scenes[0][state]" value="focus" class="scene-state" placeholder="e.g. focus, rest, energize">
                        </div>
                        <div class="stack" style="gap: 6px;">
                            <label>Audio File</label>
                            <select name="scenes[0][audio]" class="scene-audio-select">
                                <option value="">-- None --</option>
                            </select>
                        </div>
                        <div class="stack" style="gap: 6px;">
                            <label>Audio Volume (<span class="vol-val">1.0</span>)</label>
                            <input type="range" name="scenes[0][audio_volume]" min="0" max="1" step="0.01" value="1.0" oninput="this.previousElementSibling.querySelector('.vol-val').textContent = this.value">
                        </div>
                        <div class="stack" style="gap: 6px;">
                            <label>Frequency</label>
                            <input type="text" name="scenes[0][frequency]" placeholder="e.g. 6 Hz">
                        </div>
                        <div class="stack" style="gap: 6px;">
                            <label>Audio Modulation</label>
                            <select name="scenes[0][modulation]">
                                <option value="None" selected>None</option>
                                <option value="Amplitude Modulation (AM)">Amplitude Modulation (AM)</option>
                                <option value="Isochronic Pulse">Isochronic Pulse</option>
                                <option value="Monaural Beat">Monaural Beat</option>
                                <option value="Binaural Beat">Binaural Beat</option>
                                <option value="Tremolo / Slow AM">Tremolo / Slow AM</option>
                            </select>
                        </div>
                        <div class="stack" style="gap: 6px;">
                            <label>Brightness (0–100)</label>
                            <input type="range" name="scenes[0][brightness]" min="0" max="100" value="50">
                        </div>
                        <div class="stack" style="gap: 6px;">
                            <label>CCT (0–100)</label>
                            <input type="range" name="scenes[0][cct]" min="0" max="100" value="50">
                        </div>
                        <div class="stack" style="gap: 6px; grid-column: span 2;">
                            <label>RGB Color [r, g, b]</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="number" name="scenes[0][rgb_r]" min="0" max="255" value="0" placeholder="R" style="width: 80px;" class="rgb-r">
                                <input type="number" name="scenes[0][rgb_g]" min="0" max="255" value="0" placeholder="G" style="width: 80px;" class="rgb-g">
                                <input type="number" name="scenes[0][rgb_b]" min="0" max="255" value="0" placeholder="B" style="width: 80px;" class="rgb-b">
                                <div class="color-swatch" style="width: 36px; height: 36px; border-radius: 8px; background: rgb(0,0,0); border: 1px solid rgba(255,255,255,.2); flex-shrink: 0;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.12);">
            <a href="<?= e(path('/admin/vault')) ?>" class="button ghost">Back to Vault</a>
            <button type="submit" class="button primary">Save &amp; Publish Module</button>
        </div>
    </form>
</div>

<script src="<?= e(path('/assets/js/runtime.js?v=' . asset_version('assets/js/runtime.js'))) ?>"></script>
<script src="<?= e(path('/assets/js/app.js?v=' . asset_version('assets/js/app.js'))) ?>"></script>
