<!-- Player page uses full-width layout -->
<div class="player-page" style="display: grid; grid-template-columns: 1fr 300px; gap: 24px; min-height: calc(100vh - 100px);">

  <!-- LEFT: Video Player -->
  <div style="display: flex; flex-direction: column;">
    <div class="player-wrap" style="position: relative; width: 100%; aspect-ratio: 16/9; background: #000; border-radius: 8px; overflow: hidden; border: 1px solid var(--border);">
      <!-- Overlay shown before start -->
      <div class="player-overlay" id="player-overlay" style="position: absolute; inset: 0; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 10; color: white;">
        <div class="player-overlay-content" style="text-align: center; max-width: 440px; width: 90%; padding: 20px 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; margin: 0 auto;">
          <p style="font-size: 36px; margin: 0;">🎬</p>
          <h2 style="margin: 0; color: white; font-size: 20px; line-height: 1.3;"><?= e($assignment['module_name']) ?></h2>
          <p id="preflight-subtitle" style="margin: 0; color: #ccc; font-size: 13px;">Connecting to lighting controller and preparing playback...</p>
          <div id="preflight-status" style="margin: 0; font-size: 13px; opacity: .9; padding: 8px 14px; background: rgba(255,255,255,0.1); border-radius: 6px; width: 100%;"></div>
          <button id="start-btn" class="button primary" style="display:none; font-size: 16px; padding: 12px 28px; background: #19736b; color: #fff; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(25, 115, 107, 0.5);">▶ Start Session</button>
          <div id="preflight-error" style="color: #ff8080; margin-top: 4px; display:none; background: rgba(255,0,0,0.15); padding: 10px; border-radius: 6px; font-size: 13px; text-align: left; width: 100%;"></div>
        </div>
      </div>

      <!-- The video element -->
      <video id="player-video" 
        preload="metadata"
        style="display:none; width: 100%; height: 100%; object-fit: contain;"
        playsinline>
      </video>
    </div>

    <!-- Controls below video -->
    <div class="player-controls" id="player-controls" style="display:none; margin-top: 16px; align-items: center; gap: 12px; background: var(--panel-bg); padding: 16px; border-radius: 8px; border: 1px solid var(--border);">
      <button id="pause-btn" class="button ghost">⏸ Pause</button>
      <button id="stop-btn" class="button danger">⏹ Stop Session</button>
      <button id="fullscreen-btn" class="button ghost" title="Fullscreen">⛶ Fullscreen</button>
      <span style="color: var(--text); font-size: 16px; font-weight: 500; font-family: monospace; margin-left: auto;" id="elapsed-display">0:00</span>
    </div>

    <!-- Error Banner -->
    <div class="error-banner" id="runtime-error-banner" style="display: none; background: var(--danger); color: white; padding: 12px 16px; border-radius: 8px; margin-top: 16px;"></div>
  </div>

  <!-- RIGHT: Status HUD -->
  <div class="runtime-hud panel" id="runtime-hud" style="height: fit-content;">
    <div class="panel-head">
      <p class="hud-title" style="margin: 0; font-weight: 600;">Synchronization Status</p>
    </div>
    
    <div class="stack" style="padding: 16px; gap: 16px;">
      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">Connection</span>
        <span class="hud-value" style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
          <span class="connection-dot connecting" id="hud-connection-dot" style="width: 10px; height: 10px; border-radius: 50%; background: #fbbf24;"></span>
          <span id="hud-connection-text">Connecting...</span>
        </span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">Current Scene</span>
        <span class="hud-value" id="hud-scene" style="font-weight: 500;">—</span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">Elapsed</span>
        <span class="hud-value" id="hud-elapsed" style="font-weight: 500; font-family: monospace;">0:00</span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">Audio</span>
        <span class="hud-value" id="hud-audio" style="font-weight: 500;">—</span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">Brightness</span>
        <span class="hud-value" id="hud-brightness" style="font-weight: 500;">—</span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">CCT</span>
        <span class="hud-value" id="hud-cct" style="font-weight: 500;">—</span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">RGB</span>
        <span class="hud-value" style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
          <span id="hud-rgb">—</span>
          <span class="hud-rgb-swatch" id="hud-rgb-swatch" style="width: 16px; height: 16px; border-radius: 4px; border: 1px solid var(--border);"></span>
        </span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">Module</span>
        <span class="hud-value" style="font-weight: 500; text-align: right;"><?= e($assignment['module_name']) ?></span>
      </div>

      <div class="hud-row" style="display: flex; justify-content: space-between; align-items: center;">
        <span class="hud-label" style="color: var(--muted); font-size: 13px;">Plays Left</span>
        <span class="hud-value" id="plays-remaining" style="font-weight: 500;"><?= e($assignment['remaining_plays']) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Pass data to JavaScript -->
<script>
const ASSIGNMENT_ID = <?= (int) $assignment['id'] ?>;
const ESP32_IP = <?= json_encode($assignment['esp32_ip']) ?>;
const MODULE_CONFIG_URL = <?= json_encode(path('/api/tutor/module?id=' . (int) $assignment['id'])) ?>;
const API_RUNTIME_START = <?= json_encode(path('/api/runtime/start')) ?>;
const API_RUNTIME_END = <?= json_encode(path('/api/runtime/end')) ?>;
</script>
<script src="<?= e(path('/assets/js/runtime.js?v=' . asset_version('assets/js/runtime.js'))) ?>"></script>
<script>
// Player initialization
document.addEventListener('DOMContentLoaded', async function() {
  const overlay = document.getElementById('player-overlay');
  const video = document.getElementById('player-video');
  const preflightSubtitle = document.getElementById('preflight-subtitle');
  const preflightStatus = document.getElementById('preflight-status');
  const preflightError = document.getElementById('preflight-error');
  const startBtn = document.getElementById('start-btn');
  const playerControls = document.getElementById('player-controls');
  const pauseBtn = document.getElementById('pause-btn');
  const stopBtn = document.getElementById('stop-btn');
  const errorBanner = document.getElementById('runtime-error-banner');
  const elapsedDisplay = document.getElementById('elapsed-display');
  const connDot = document.getElementById('hud-connection-dot');
  const connText = document.getElementById('hud-connection-text');

  let runtime = null;
  let logId = null;
  let sessionStarted = false;

  // --- Pre-flight: validate IP, ping WLED, load config ---
  async function preflight() {
    preflightStatus.textContent = 'Fetching module configuration...';
    preflightError.style.display = 'none';

    let configData;
    try {
      const resp = await fetch(MODULE_CONFIG_URL, { credentials: 'same-origin' });
      if (!resp.ok) throw new Error('Failed to load module config (HTTP ' + resp.status + ')');
      configData = await resp.json();
    } catch (err) {
      showPreflightError('Could not load module configuration: ' + err.message);
      return;
    }

    // Validate IP
    if (!/^(\d{1,3}\.){3}\d{1,3}$/.test(ESP32_IP)) {
      showPreflightError('The stored ESP32 IP address is invalid. Please contact your administrator.');
      return;
    }

    // Mixed content check
    if (window.location.protocol === 'https:' && ESP32_IP) {
      preflightStatus.textContent = 'Note: This page is on HTTPS. WLED communication requires HTTP. Attempting anyway...';
    }

    preflightStatus.textContent = 'Connecting to classroom lighting controller (' + ESP32_IP + ')...';

    // Ping WLED
    const wledClient = new NeurosattvaRuntime.WLEDClient(ESP32_IP);
    try {
      const alive = await wledClient.ping();
      if (!alive) throw new Error('No valid WLED response received.');
    } catch (err) {
      showPreflightError(
        'Unable to connect to the classroom lighting controller. ' +
        'Confirm that this computer and ESP32 are connected to the same network.\n\nDetails: ' + err.message
      );
      connDot.style.background = '#ef4444';
      connText.textContent = 'Disconnected';
      return;
    }

    if (preflightSubtitle) {
      preflightSubtitle.textContent = 'Lighting controller connected! Click below to start playback.';
    }
    preflightStatus.textContent = '✓ Lighting controller connected. Ready to start.';
    connDot.style.background = '#22c55e';
    connText.textContent = 'Connected (' + ESP32_IP + ')';
    startBtn.style.display = 'inline-flex';
    startBtn.dataset.configJson = JSON.stringify(configData);
  }

  function showPreflightError(msg) {
    preflightError.textContent = msg;
    preflightError.style.display = 'block';
    preflightStatus.textContent = '✗ Pre-flight check failed.';
  }

  // Start button
  startBtn?.addEventListener('click', async function() {
    const configData = JSON.parse(this.dataset.configJson);
    await startSession(configData);
  });

  async function startSession(configData) {
    try {
      // Log session start
      const startResp = await fetch(API_RUNTIME_START, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'assignment_id=' + ASSIGNMENT_ID + '&device_ip=' + encodeURIComponent(ESP32_IP),
      });
      const startData = await startResp.json();
      if (!startData.success) throw new Error(startData.error || 'Failed to start session');
      logId = startData.log_id;
    } catch (err) {
      showError('Session start failed: ' + err.message);
      return;
    }

    // Show video, hide overlay
    overlay.style.display = 'none';
    video.style.display = 'block';
    playerControls.style.display = 'flex';
    sessionStarted = true;

    runtime = new NeurosattvaRuntime.RuntimeController({
      config: configData,
      videoElement: video,
      hudElement: document.getElementById('runtime-hud'),
      onEnd: handleSessionEnd,
      testMode: false,
    });

    try {
      await runtime.init();
      await runtime.start();
    } catch (err) {
      showError('Playback error: ' + err.message);
      await endSession(false, err.message);
    }
  }

  async function handleSessionEnd({ completed, error }) {
    await endSession(completed, error);
    // Update plays remaining display
    const playsEl = document.getElementById('plays-remaining');
    if (playsEl) {
      const current = parseInt(playsEl.textContent);
      if (!isNaN(current) && current > 0) playsEl.textContent = current - 1;
    }
    // Show finished overlay
    overlay.style.display = 'flex';
    document.getElementById('preflight-status').textContent = completed
      ? '✓ Session completed successfully.'
      : '⚠ Session stopped.';
    startBtn.style.display = 'none';
    video.style.display = 'none';
    playerControls.style.display = 'none';
  }

  async function endSession(completed, error) {
    if (!logId) return;
    try {
      await fetch(API_RUNTIME_END, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'log_id=' + logId + '&assignment_id=' + ASSIGNMENT_ID +
              '&completed=' + (completed ? '1' : '0') +
              (error ? '&error=' + encodeURIComponent(error) : ''),
      });
    } catch (e) {
      console.error('Failed to log session end:', e);
    }
    logId = null;
  }

  // Pause/resume
  pauseBtn?.addEventListener('click', function() {
    if (!runtime) return;
    if (video.paused) {
      runtime.resume();
      this.textContent = '⏸ Pause';
    } else {
      runtime.pause();
      this.textContent = '▶ Resume';
    }
  });

  const fullscreenBtn = document.getElementById('fullscreen-btn');
  const playerWrap = document.querySelector('.player-wrap');

  fullscreenBtn?.addEventListener('click', toggleFullscreen);
  playerWrap?.addEventListener('dblclick', toggleFullscreen);

  function toggleFullscreen() {
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
      if (playerWrap.requestFullscreen) playerWrap.requestFullscreen();
      else if (playerWrap.webkitRequestFullscreen) playerWrap.webkitRequestFullscreen();
      else if (video.requestFullscreen) video.requestFullscreen();
    } else {
      if (document.exitFullscreen) document.exitFullscreen();
      else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
    }
  }

  // Stop
  stopBtn?.addEventListener('click', async function() {
    if (runtime) await runtime.stop();
    await handleSessionEnd({ completed: false, error: null });
  });

  // Listen for WLED errors
  document.addEventListener('wled-error', function(e) {
    const banner = document.getElementById('runtime-error-banner');
    banner.textContent = '⚠ Lighting error: ' + (e.detail || 'WLED communication failed');
    banner.style.display = 'block';
  });

  function showError(msg) {
    errorBanner.textContent = msg;
    errorBanner.style.display = 'block';
  }

  // Elapsed time display
  setInterval(function() {
    if (runtime && !runtime.isPaused) {
      const s = Math.floor(runtime.currentTime);
      elapsedDisplay.textContent = Math.floor(s/60) + ':' + String(s%60).padStart(2,'0');
    }
  }, 250);

  // Start pre-flight automatically
  await preflight();
});
</script>
