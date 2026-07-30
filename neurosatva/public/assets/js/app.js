const toggle = document.querySelector('[data-menu-toggle]');
const sidebar = document.querySelector('[data-sidebar]');

if (toggle && sidebar) {
  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });
}

const authCard = document.querySelector('[data-auth-card]');
const flipCard = document.querySelector('[data-flip-card]');
const loginPanel = document.querySelector('[data-login-panel]');
const registerPanel = document.querySelector('[data-register-panel]');
const showRegister = document.querySelector('[data-show-register]');
const showLogin = document.querySelector('[data-show-login]');

const showLoginPanel = () => {
  if (!authCard || !loginPanel || !registerPanel) return;
  authCard.classList.remove('show-register');
  loginPanel.setAttribute('aria-hidden', 'false');
  registerPanel.setAttribute('aria-hidden', 'true');
  loginPanel.inert = false;
  registerPanel.inert = true;
};

const showRegisterPanel = () => {
  if (!authCard || !loginPanel || !registerPanel) return;
  authCard.classList.add('show-register');
  loginPanel.setAttribute('aria-hidden', 'true');
  registerPanel.setAttribute('aria-hidden', 'false');
  loginPanel.inert = true;
  registerPanel.inert = false;
};

if (showRegister) {
  showRegister.addEventListener('click', () => {
    showRegisterPanel();
  });
}

if (showLogin) {
  showLogin.addEventListener('click', () => {
    showLoginPanel();
  });
}

if (authCard) {
  let rafId = 0;
  const updateCardMotion = (event) => {
    if (rafId) return;
    rafId = requestAnimationFrame(() => {
      const rect = authCard.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width;
      const y = (event.clientY - rect.top) / rect.height;
      const tiltX = (0.5 - y) * 5;
      const tiltY = (x - 0.5) * 6;
      authCard.style.setProperty('--pointer-x', `${x * 100}%`);
      authCard.style.setProperty('--pointer-y', `${y * 100}%`);
      authCard.style.setProperty('--tilt-x', `${tiltX}deg`);
      authCard.style.setProperty('--tilt-y', `${tiltY}deg`);
      authCard.style.setProperty('--shadow-x', `${(x - 0.5) * 24}px`);
      authCard.style.setProperty('--shadow-y', `${24 + y * 14}px`);
      document.body.style.setProperty('--pointer-x', `${x * 100}%`);
      document.body.style.setProperty('--pointer-y', `${y * 100}%`);
      rafId = 0;
    });
  };

  authCard.addEventListener('pointermove', updateCardMotion);
  authCard.addEventListener('pointerleave', () => {
    authCard.style.setProperty('--tilt-x', '0deg');
    authCard.style.setProperty('--tilt-y', '0deg');
    authCard.style.setProperty('--shadow-x', '0px');
    authCard.style.setProperty('--shadow-y', '28px');
  });
}

document.querySelectorAll('[data-loading-form]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (form.matches('[data-auth-submit]')) {
      if (form.dataset.submitting === '1') return;
      if (!form.checkValidity()) return;
      event.preventDefault();
      form.dataset.submitting = '1';
      if (authCard) {
        authCard.classList.add('is-submitting', 'show-success');
      }
      document.body.classList.add('auth-exiting');
      setTimeout(() => form.submit(), 1180);
    }

    const button = form.querySelector('[data-loading-button]');
    if (!button) return;
    button.disabled = true;
    button.classList.add('is-loading');
  });
});

const popup = document.querySelector('[data-success-popup]');
const closePopup = document.querySelector('[data-close-popup]');
const popupBackLogin = document.querySelector('[data-popup-back-login]');

const closeSuccessPopup = () => {
  if (!popup) return;
  showLoginPanel();
  popup.classList.add('closing');
  setTimeout(() => popup.remove(), 180);
};

if (popup && closePopup) {
  closePopup.addEventListener('click', closeSuccessPopup);
}

if (popup && popupBackLogin) {
  popupBackLogin.addEventListener('click', () => {
    showLoginPanel();
    closeSuccessPopup();
  });
}

document.querySelectorAll('.glass-button').forEach((button) => {
  button.addEventListener('click', (event) => {
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);

    ripple.className = 'button-ripple';
    ripple.style.width = `${size}px`;
    ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${event.clientY - rect.top - size / 2}px`;

    button.appendChild(ripple);
    setTimeout(() => ripple.remove(), 620);
  });
});

if (popup) {
  popup.addEventListener('click', (event) => {
    if (event.target === popup) {
    popup.classList.add('closing');
    setTimeout(() => popup.remove(), 180);
    }
  });
}

document.querySelectorAll('[data-open-modal]').forEach((trigger) => {
  trigger.addEventListener('click', () => {
    const modal = document.getElementById(trigger.dataset.openModal);
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  });
});

document.querySelectorAll('[data-close-modal]').forEach((trigger) => {
  trigger.addEventListener('click', () => {
    const modal = trigger.closest('.glass-modal');
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  });
});

document.querySelectorAll('.glass-modal').forEach((modal) => {
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
    }
  });
});

document.querySelectorAll('[data-table-search]').forEach((input) => {
  input.addEventListener('input', () => {
    const table = document.querySelector('[data-searchable-table]');
    if (!table) return;
    const query = input.value.trim().toLowerCase();
    table.querySelectorAll('tbody tr').forEach((row) => {
      row.hidden = query !== '' && !row.textContent.toLowerCase().includes(query);
    });
  });
});

document.querySelectorAll('[data-open-pasted-link]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = button.closest('form')?.querySelector('[name="storage_path"]');
    const link = input?.value.trim();

    if (!link) {
      alert('Please paste a link first.');
      input?.focus();
      return;
    }

    try {
      const url = new URL(link);
      if (!['http:', 'https:'].includes(url.protocol)) throw new Error('Unsupported link');
      window.open(url.href, '_blank', 'noopener,noreferrer');
    } catch (error) {
      alert('Please enter a valid link starting with http:// or https://.');
      input?.focus();
    }
  });
});

/* ===== SCENE BUILDER ===== */

function createSceneRow(index, data = {}) {
  const duration = data.duration !== undefined ? data.duration : 60;
  const state = data.state || 'focus';
  const audio = data.audio || '';
  const audioVolume = data.audio_volume !== undefined ? data.audio_volume : 1.0;
  const frequency = data.frequency || '';
  const modulation = data.modulation || 'None';
  const brightness = data.brightness !== undefined ? data.brightness : 50;
  const cct = data.cct !== undefined ? data.cct : 50;

  let r = 0, g = 0, b = 0;
  if (Array.isArray(data.rgb)) {
    r = data.rgb[0] ?? 0;
    g = data.rgb[1] ?? 0;
    b = data.rgb[2] ?? 0;
  } else {
    r = data.rgb_r ?? data.r ?? 0;
    g = data.rgb_g ?? data.g ?? 0;
    b = data.rgb_b ?? data.b ?? 0;
  }

  let audioOptions = '<option value="">-- None --</option>';
  const existingAudioSelect = document.querySelector('.scene-audio-select');
  if (existingAudioSelect) {
    Array.from(existingAudioSelect.options).forEach(opt => {
      if (!opt.value) return;
      const sel = opt.value === audio ? 'selected' : '';
      audioOptions += `<option value="${opt.value}" ${sel}>${opt.text}</option>`;
    });
  }
  if (audio && !audioOptions.includes(`value="${audio}"`)) {
    audioOptions += `<option value="${audio}" selected>${audio}</option>`;
  }

  return `
  <div class="scene-row" data-index="${index}">
    <div class="scene-header">
      <span class="drag-handle" style="cursor: grab; opacity: .5;">⠿</span>
      <span class="scene-number">${index + 1}</span>
      <span class="scene-summary">Scene ${index + 1} — ${duration}s — ${state}</span>
      <button type="button" class="button ghost small duplicate-scene">Copy</button>
      <button type="button" class="button danger small delete-scene">Delete</button>
    </div>
    <div class="scene-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 18px; border-top: 1px solid rgba(255,255,255,.1);">
      <div class="stack" style="gap: 6px;">
        <label>Duration (seconds)</label>
        <input type="number" name="scenes[${index}][duration]" min="1" value="${duration}" class="scene-duration">
      </div>
      <div class="stack" style="gap: 6px;">
        <label>State Name</label>
        <input type="text" name="scenes[${index}][state]" value="${state}" class="scene-state" placeholder="e.g. focus, rest">
      </div>
      <div class="stack" style="gap: 6px;">
        <label>Audio File</label>
        <select name="scenes[${index}][audio]" class="scene-audio-select" data-selected="${audio}">
          ${audioOptions}
        </select>
      </div>
      <div class="stack" style="gap: 6px;">
        <label>Audio Volume (<span class="vol-val">${audioVolume}</span>)</label>
        <input type="range" name="scenes[${index}][audio_volume]" min="0" max="1" step="0.01" value="${audioVolume}" oninput="this.previousElementSibling.querySelector('.vol-val').textContent = this.value">
      </div>
      <div class="stack" style="gap: 6px;">
        <label>Frequency</label>
        <input type="text" name="scenes[${index}][frequency]" value="${frequency}" placeholder="e.g. 6 Hz">
      </div>
      <div class="stack" style="gap: 6px;">
        <label>Audio Modulation</label>
        <select name="scenes[${index}][modulation]">
          <option value="None" ${['None', ''].includes(modulation) ? 'selected' : ''}>None</option>
          <option value="Amplitude Modulation (AM)" ${['Amplitude Modulation (AM)', 'AM'].includes(modulation) ? 'selected' : ''}>Amplitude Modulation (AM)</option>
          <option value="Isochronic Pulse" ${['Isochronic Pulse', 'Isochronic'].includes(modulation) ? 'selected' : ''}>Isochronic Pulse</option>
          <option value="Monaural Beat" ${['Monaural Beat', 'Monaural'].includes(modulation) ? 'selected' : ''}>Monaural Beat</option>
          <option value="Binaural Beat" ${['Binaural Beat', 'Binaural'].includes(modulation) ? 'selected' : ''}>Binaural Beat</option>
          <option value="Tremolo / Slow AM" ${['Tremolo / Slow AM', 'Tremolo'].includes(modulation) ? 'selected' : ''}>Tremolo / Slow AM</option>
        </select>
      </div>
      <div class="stack" style="gap: 6px;">
        <label>Brightness (0–100)</label>
        <input type="range" name="scenes[${index}][brightness]" min="0" max="100" value="${brightness}">
      </div>
      <div class="stack" style="gap: 6px;">
        <label>CCT (0–100)</label>
        <input type="range" name="scenes[${index}][cct]" min="0" max="100" value="${cct}">
      </div>
      <div class="stack" style="gap: 6px; grid-column: span 2;">
        <label>RGB Color [r, g, b]</label>
        <div style="display: flex; gap: 8px; align-items: center;">
          <input type="number" name="scenes[${index}][rgb_r]" min="0" max="255" value="${r}" placeholder="R" style="width: 80px;" class="rgb-r">
          <input type="number" name="scenes[${index}][rgb_g]" min="0" max="255" value="${g}" placeholder="G" style="width: 80px;" class="rgb-g">
          <input type="number" name="scenes[${index}][rgb_b]" min="0" max="255" value="${b}" placeholder="B" style="width: 80px;" class="rgb-b">
          <div class="color-swatch" style="width: 36px; height: 36px; border-radius: 8px; background: rgb(${r},${g},${b}); border: 1px solid rgba(255,255,255,.2); flex-shrink: 0;"></div>
        </div>
      </div>
    </div>
  </div>`;
}

function initSceneBuilder() {
  const addBtn = document.getElementById('add-scene-btn');
  const list = document.getElementById('scene-list');
  if (!list) return;

  const updateIndices = () => {
    const rows = list.querySelectorAll('.scene-row');
    rows.forEach((row, i) => {
      row.dataset.index = i;
      const num = row.querySelector('.scene-number');
      if (num) num.textContent = i + 1;

      const r = row.querySelector('.rgb-r')?.value || 0;
      const g = row.querySelector('.rgb-g')?.value || 0;
      const b = row.querySelector('.rgb-b')?.value || 0;
      const d = row.querySelector('.scene-duration')?.value || '60';
      const st = row.querySelector('.scene-state')?.value || 'focus';

      const sum = row.querySelector('.scene-summary');
      if (sum) sum.textContent = `Scene ${i + 1} — ${d}s — ${st}`;

      const swatch = row.querySelector('.color-swatch');
      if (swatch) swatch.style.backgroundColor = `rgb(${r},${g},${b})`;
    });
  };

  window.updateSceneIndices = updateIndices;

  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const idx = list.children.length;
      list.insertAdjacentHTML('beforeend', createSceneRow(idx));
      updateIndices();
    });
  }

  list.addEventListener('click', (e) => {
    const header = e.target.closest('.scene-header');
    if (header && !e.target.closest('button, .drag-handle')) {
      const body = header.nextElementSibling;
      if (body) {
        body.style.display = (body.style.display === 'none') ? 'grid' : 'none';
      }
    }

    const delBtn = e.target.closest('.delete-scene, [data-delete-scene]');
    if (delBtn) {
      e.stopPropagation();
      const row = delBtn.closest('.scene-row');
      if (row) {
        row.remove();
        updateIndices();
      }
    }

    const dupBtn = e.target.closest('.duplicate-scene, [data-dup-scene]');
    if (dupBtn) {
      e.stopPropagation();
      const row = dupBtn.closest('.scene-row');
      if (row) {
        const clone = row.cloneNode(true);
        row.after(clone);
        updateIndices();
      }
    }
  });

  list.addEventListener('input', (e) => {
    if (e.target.matches('.rgb-r, .rgb-g, .rgb-b, .scene-duration, .scene-state')) {
      updateIndices();
    }
  });

  let draggedRow = null;
  list.addEventListener('dragstart', (e) => {
    if (e.target.matches('.scene-row')) {
      draggedRow = e.target;
      e.dataTransfer.effectAllowed = 'move';
      setTimeout(() => e.target.style.opacity = '0.5', 0);
    }
  });

  list.addEventListener('dragend', (e) => {
    if (e.target.matches('.scene-row')) {
      e.target.style.opacity = '1';
      draggedRow = null;
      updateIndices();
    }
  });

  list.addEventListener('dragover', (e) => {
    e.preventDefault();
    if (!draggedRow) return;
    const afterElement = getDragAfterElement(list, e.clientY);
    if (afterElement == null) {
      list.appendChild(draggedRow);
    } else {
      list.insertBefore(draggedRow, afterElement);
    }
  });

  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.scene-row:not([style*="opacity: 0.5"])')];
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return { offset: offset, element: child };
      } else {
        return closest;
      }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }
}

/* ===== UNIVERSAL DROPZONE ENGINE (with type validation) ===== */

/**
 * Returns the lowercase extension of a filename, e.g. "mp4", "json".
 */
function getFileExt(filename) {
  return filename.split('.').pop().toLowerCase();
}

/**
 * Reads the allowed extensions for a dropzone element.
 * Falls back to reading the input's accept attribute if no data-allowed-exts is set.
 * Returns an array of lowercase extension strings, e.g. ["mp4", "mov"].
 */
function getAllowedExts(zone, input) {
  if (zone.dataset.allowedExts) {
    return zone.dataset.allowedExts.split(',').map(e => e.trim().toLowerCase());
  }
  // Fallback: parse the accept attribute
  const accept = input.accept || '';
  return accept.split(',').map(s => s.trim().replace(/^\./, '').toLowerCase()).filter(Boolean);
}

/**
 * Shows an inline error message inside (or just below) the dropzone.
 * Auto-dismisses after 4 s.
 */
function showDropzoneError(zone, message) {
  // Reuse an existing error el if already present
  let errEl = zone.parentElement.querySelector('.dropzone-error-msg');
  if (!errEl) {
    errEl = document.createElement('p');
    errEl.className = 'dropzone-error-msg';
    errEl.style.cssText = [
      'margin: 6px 0 0',
      'padding: 7px 12px',
      'background: rgba(239,68,68,.18)',
      'border: 1px solid rgba(239,68,68,.45)',
      'border-radius: 8px',
      'color: #fca5a5',
      'font-size: 12px',
      'font-weight: 500',
      'animation: fadeInDown .18s ease',
    ].join(';');
    zone.insertAdjacentElement('afterend', errEl);
  }
  errEl.textContent = '⚠ ' + message;
  clearTimeout(errEl._timer);
  errEl._timer = setTimeout(() => errEl.remove(), 4000);
}

/**
 * Removes any error message for the dropzone.
 */
function clearDropzoneError(zone) {
  const errEl = zone.parentElement?.querySelector('.dropzone-error-msg');
  if (errEl) errEl.remove();
}

/**
 * Validates an array of File objects against allowed extensions.
 * Returns { valid: File[], rejected: string[] }
 */
function partitionFiles(files, allowedExts) {
  const valid = [];
  const rejected = [];
  for (const f of files) {
    if (allowedExts.includes(getFileExt(f.name))) {
      valid.push(f);
    } else {
      rejected.push(f.name);
    }
  }
  return { valid, rejected };
}

function initAllDropZones() {
  document.querySelectorAll('.drop-zone').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]');
    if (!input) return;
    bindDropZoneEvents(zone, input);
  });
}

function bindDropZoneEvents(zone, input) {
  if (zone.dataset.dropzoneBound === '1') return;
  zone.dataset.dropzoneBound = '1';

  zone.style.cursor = 'pointer';

  zone.addEventListener('click', (e) => {
    if (e.target.closest('button, a')) return;
    if (e.target === input) return;
    input.click();
  });

  ['dragenter', 'dragover'].forEach(eventName => {
    zone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.add('dragging');
    }, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    zone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.remove('dragging');
    }, false);
  });

  zone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (!dt || !dt.files || !dt.files.length) return;

    const allowedExts = getAllowedExts(zone, input);
    const { valid, rejected } = partitionFiles(Array.from(dt.files), allowedExts);

    if (rejected.length) {
      const label = zone.dataset.allowedLabel || allowedExts.map(x => '.' + x).join(', ');
      showDropzoneError(zone,
        `Wrong file type${rejected.length > 1 ? 's' : ''}: ${rejected.join(', ')}. Allowed: ${label}`);
    }

    if (!valid.length) return;

    // Assign only valid files to the input
    try {
      const container = new DataTransfer();
      valid.forEach(f => container.items.add(f));
      input.files = container.files;
    } catch (_) {
      // DataTransfer not supported — fall back (browser will re-validate on submit)
    }

    if (rejected.length === 0) clearDropzoneError(zone);
    handleFileSelection(zone, input);
  });

  input.addEventListener('change', () => {
    if (!input.files || !input.files.length) return;

    const allowedExts = getAllowedExts(zone, input);
    const { valid, rejected } = partitionFiles(Array.from(input.files), allowedExts);

    if (rejected.length) {
      const label = zone.dataset.allowedLabel || allowedExts.map(x => '.' + x).join(', ');
      showDropzoneError(zone,
        `Wrong file type${rejected.length > 1 ? 's' : ''}: ${rejected.join(', ')}. Allowed: ${label}`);

      if (!valid.length) {
        // All files invalid — clear the input and abort
        input.value = '';
        return;
      }

      // Some valid files remain — keep only those
      try {
        const container = new DataTransfer();
        valid.forEach(f => container.items.add(f));
        input.files = container.files;
      } catch (_) { /* ignore */ }
    } else {
      clearDropzoneError(zone);
    }

    handleFileSelection(zone, input);
  });
}

function handleFileSelection(zone, input) {
  if (!input.files || !input.files.length) return;

  const textEl = zone.querySelector('.drop-text') || zone.querySelector('.drop-zone-text') || zone.querySelector('span');
  if (textEl) {
    if (input.files.length === 1) {
      textEl.textContent = '✓ Selected: ' + input.files[0].name;
    } else {
      textEl.textContent = `✓ ${input.files.length} files selected`;
    }
  }
  zone.classList.add('has-file');

  // Thumbnail preview
  if (input.id === 'thumb-input') {
    const previewContainer = document.getElementById('thumb-preview');
    if (previewContainer && input.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => {
        previewContainer.innerHTML = `<img src="${e.target.result}" style="max-height: 90px; border-radius: 8px; border: 1px solid rgba(255,255,255,.2); margin-top: 6px;">`;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Master Video feedback
  if (input.id === 'video-input') {
    const videoFilenameEl = document.getElementById('video-filename');
    if (videoFilenameEl && input.files[0]) {
      const file = input.files[0];
      const mb = (file.size / (1024 * 1024)).toFixed(1);
      videoFilenameEl.textContent = `✓ Selected Video: ${file.name} (${mb} MB)`;
    }
  }

  // Audio files list & scene dropdown sync
  if (input.id === 'audio-input') {
    const audioListEl = document.getElementById('audio-file-list');
    if (audioListEl) {
      audioListEl.innerHTML = '';
      Array.from(input.files).forEach(f => {
        const row = document.createElement('div');
        row.className = 'audio-file-row';
        row.innerHTML = `<span style="font-size: 13px; color: #a78bfa;">🎵 ${f.name} (${(f.size / 1024).toFixed(0)} KB)</span>`;
        audioListEl.appendChild(row);
      });
    }

    // Sync all scene audio selects
    document.querySelectorAll('.scene-audio-select').forEach(select => {
      const currentSelected = select.value;
      select.innerHTML = '<option value="">-- None --</option>';
      Array.from(input.files).forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.name;
        opt.textContent = f.name;
        if (f.name === currentSelected) opt.selected = true;
        select.appendChild(opt);
      });
    });
  }

  // Config JSON auto-import
  if (input.id === 'config-json-input') {
    const file = input.files[0];
    if (file && getFileExt(file.name) === 'json') {
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const config = JSON.parse(e.target.result);
          populateFormFromConfig(config);
        } catch (err) {
          showDropzoneError(zone, 'Invalid JSON: ' + err.message);
        }
      };
      reader.readAsText(file);
    }
  }
}

function populateFormFromConfig(config) {
  if (!config || typeof config !== 'object') return;

  const nameInput = document.querySelector('input[name="name"]');
  if (nameInput && (config.module_name || config.name || config.title)) {
    nameInput.value = config.module_name || config.name || config.title;
  }

  const descTextarea = document.querySelector('textarea[name="description"]');
  if (descTextarea && config.description) {
    descTextarea.value = config.description;
  }

  const videoName = config.video || config.video_name;
  const videoFilenameEl = document.getElementById('video-filename');
  if (videoName && videoFilenameEl) {
    videoFilenameEl.textContent = '✓ Referenced video in config: ' + videoName;
  }

  const timeline = config.timeline || config.scenes;
  if (Array.isArray(timeline) && timeline.length > 0) {
    const list = document.getElementById('scene-list');
    if (list) {
      list.innerHTML = '';
      timeline.forEach((scene, i) => {
        list.insertAdjacentHTML('beforeend', createSceneRow(i, scene));
      });
      if (typeof window.updateSceneIndices === 'function') {
        window.updateSceneIndices();
      }
    }
  }

  const banner = document.getElementById('config-json-banner');
  if (banner) {
    const sceneCount = Array.isArray(timeline) ? timeline.length : 0;
    banner.style.display = 'block';
    banner.innerHTML = `<strong>✓ Config Loaded!</strong> Auto-filled module details and ${sceneCount} scene timeline item(s) from JSON.`;
  }
}

function initAudioManager() {
  const list = document.getElementById('audio-file-list');
  if (!list) return;

  let currentAudio = null;
  let currentBtn = null;

  list.addEventListener('click', (e) => {
    const previewBtn = e.target.closest('.preview-btn');
    if (previewBtn) {
      const url = previewBtn.dataset.url;
      if (currentAudio && currentBtn === previewBtn) {
        if (!currentAudio.paused) {
          currentAudio.pause();
          previewBtn.textContent = '▶';
        } else {
          currentAudio.play();
          previewBtn.textContent = '⏸';
        }
      } else {
        if (currentAudio) {
          currentAudio.pause();
          if (currentBtn) currentBtn.textContent = '▶';
        }
        currentAudio = new Audio(url);
        currentAudio.play();
        currentBtn = previewBtn;
        previewBtn.textContent = '⏸';
        currentAudio.onended = () => previewBtn.textContent = '▶';
      }
    }
  });
}

function validateIp(ip) {
  return /^(\d{1,3}\.){3}\d{1,3}$/.test(ip);
}

function initTestModal() {
  const triggers = document.querySelectorAll('[data-test-module]');
  const modal = document.getElementById('test-modal');
  if (!triggers.length || !modal) return;

  const backdrop = modal.closest('.modal-backdrop') || modal;
  const ipInput = modal.querySelector('input[name="esp32_ip"]');
  const ipStatus = modal.querySelector('.ip-status');

  triggers.forEach(t => {
    t.addEventListener('click', () => {
      backdrop.classList.add('open');
    });
  });

  if (ipInput) {
    ipInput.addEventListener('input', () => {
      if (validateIp(ipInput.value)) {
        ipStatus.textContent = '✓';
        ipStatus.className = 'ip-status valid';
      } else {
        ipStatus.textContent = 'Invalid format';
        ipStatus.className = 'ip-status invalid';
      }
    });
  }

  const closeBtn = modal.querySelector('[data-close-modal]');
  if (closeBtn) {
    closeBtn.addEventListener('click', () => backdrop.classList.remove('open'));
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initSceneBuilder();
  initAllDropZones();
  initAudioManager();
  initTestModal();
});
