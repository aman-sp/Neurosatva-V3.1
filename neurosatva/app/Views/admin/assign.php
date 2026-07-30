<div class="panel">
  <div class="panel-head">
    <div>
      <h2>Assign Module to Tutor</h2>
      <p>Configure a module assignment with playback limits and lighting controller IP.</p>
    </div>
    <a href="<?= e(path('/admin/assignments')) ?>" class="button ghost small">View Assignments</a>
  </div>

  <form method="post" action="<?= e(path('/admin/assign')) ?>">
    <?= csrf_field() ?>
    <div class="assign-form-grid" style="display: grid; gap: 20px; max-width: 560px;">
      <!-- Tutor select -->
      <label class="stack">
        <span>Select Tutor / Teacher *</span>
        <select name="tutor_id" required>
          <option value="">— Choose a tutor —</option>
          <?php foreach ($tutors as $t): ?>
            <option value="<?= e($t['id']) ?>"><?= e($t['name']) ?> (<?= e($t['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>

      <!-- Module select -->
      <label class="stack">
        <span>Select Module from Vault *</span>
        <select name="module_id" required>
          <option value="">— Choose a module —</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?= e($m['id']) ?>"><?= e($m['name']) ?> — v<?= e($m['version']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <!-- ESP32 IP -->
      <label class="stack">
        <span>Classroom ESP32 / WLED IP Address *</span>
        <div class="ip-input-wrap">
          <input type="text" name="esp32_ip" id="esp32-ip-input"
            placeholder="192.168.1.25" pattern="^(\d{1,3}\.){3}\d{1,3}$"
            required autocomplete="off">
          <span class="ip-status" id="ip-status"></span>
        </div>
        <small style="color: rgba(255,255,255,.6); font-weight: 400;">Connects to the ESP32 lighting controller of that particular classroom.</small>
      </label>

      <!-- Plays -->
      <label class="stack">
        <span>Allowed Playback Count (Number of Plays) *</span>
        <input type="number" name="plays" min="1" max="9999" value="5" required>
        <small style="color: rgba(255,255,255,.6); font-weight: 400;">Set how many times the teacher can play this module.</small>
      </label>

      <!-- Expiry date (optional) -->
      <label class="stack">
        <span>Assignment Expiry (Optional)</span>
        <input type="date" name="expiry_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        <small style="color: rgba(255,255,255,.6); font-weight: 400;">Leave empty for no expiry date.</small>
      </label>
    </div>

    <div style="margin-top: 28px; display: flex; gap: 12px;">
      <button type="submit" class="button primary">Assign Module</button>
      <a href="<?= e(path('/admin/vault')) ?>" class="button ghost">Cancel</a>
    </div>
  </form>
</div>

<script>
document.getElementById('esp32-ip-input')?.addEventListener('input', function() {
  const ip = this.value.trim();
  const status = document.getElementById('ip-status');
  const valid = /^(\d{1,3}\.){3}\d{1,3}$/.test(ip) &&
    ip.split('.').every(n => parseInt(n) >= 0 && parseInt(n) <= 255);
  status.textContent = ip ? (valid ? '✓ Valid IP' : '✗ Invalid IP') : '';
  status.style.color = valid ? '#5eead4' : '#fca5a5';
});
</script>
