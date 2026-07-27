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

document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const assigned = form.querySelector('[name="confirm_assigned_delete"]');
    const message = assigned
      ? 'This module is assigned to tutors. Delete module, files, metadata, and assignments?'
      : 'Delete this module and all files?';
    if (!confirm(message)) event.preventDefault();
  });
});

document.querySelectorAll('[data-module-builder]').forEach((builder) => {
  const sceneList = builder.querySelector('[data-scene-list]');
  const audioInput = builder.querySelector('[data-audio-upload]');
  const pendingAudioList = builder.querySelector('[data-pending-audio-list]');

  const audioNames = () => {
    const existing = [...builder.querySelectorAll('[data-audio-name]')].map((row) => row.dataset.audioName);
    const uploads = audioInput?.files ? [...audioInput.files].map((file) => file.name) : [];
    return [...new Set([...existing, ...uploads].filter(Boolean))];
  };

  const refreshAudioSelects = () => {
    const names = audioNames();
    if (pendingAudioList) {
      pendingAudioList.innerHTML = '';
      if (audioInput?.files?.length) {
        [...audioInput.files].forEach((file) => {
          const row = document.createElement('div');
          row.className = 'audio-row pending';
          row.innerHTML = `<strong>${file.name}</strong><span class="hint">Ready to save</span><span></span><span></span>`;
          pendingAudioList.appendChild(row);
        });
      }
    }
    builder.querySelectorAll('[data-scene-audio]').forEach((select) => {
      const selected = select.value;
      select.innerHTML = '';
      names.forEach((name) => {
        const option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        option.selected = selected === name;
        select.appendChild(option);
      });
    });
  };

  const wireScene = (scene) => {
    scene.querySelector('[data-delete-scene]')?.addEventListener('click', () => {
      if (sceneList.children.length > 1) scene.remove();
    });
    scene.querySelector('[data-duplicate-scene]')?.addEventListener('click', () => {
      const clone = scene.cloneNode(true);
      scene.after(clone);
      wireScene(clone);
      refreshAudioSelects();
    });
    scene.querySelectorAll('[data-move-scene]').forEach((button) => {
      button.addEventListener('click', () => {
        if (button.dataset.moveScene === 'up' && scene.previousElementSibling) scene.before(scene.previousElementSibling);
        if (button.dataset.moveScene === 'down' && scene.nextElementSibling) scene.after(scene.nextElementSibling);
      });
    });
  };

  audioInput?.addEventListener('change', refreshAudioSelects);
  builder.querySelector('[data-add-scene]')?.addEventListener('click', () => {
    const first = sceneList.querySelector('[data-scene]');
    if (!first) return;
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach((input) => {
      if (!['scene_duration[]', 'scene_audio_volume[]', 'scene_brightness[]', 'scene_cct[]', 'scene_rgb_r[]', 'scene_rgb_g[]', 'scene_rgb_b[]'].includes(input.name)) {
        input.value = '';
      }
    });
    sceneList.appendChild(clone);
    wireScene(clone);
    refreshAudioSelects();
  });
  sceneList.querySelectorAll('[data-scene]').forEach(wireScene);
  refreshAudioSelects();
});

class ModuleLoader {
  constructor(payload) { this.payload = payload; }
  videoUrl() { return this.payload.video_url; }
  timeline() { return this.payload.config.timeline || []; }
  audioUrls() { return this.payload.audio_urls || {}; }
}

class TimelineEngine {
  constructor(timeline) {
    this.timeline = timeline;
    this.boundaries = [];
    let cursor = 0;
    timeline.forEach((scene, index) => {
      const start = cursor;
      cursor += Number(scene.duration || 0);
      this.boundaries.push({ index, start, end: cursor, scene });
    });
  }
  sceneAt(seconds) {
    return this.boundaries.find((entry) => seconds >= entry.start && seconds < entry.end) || this.boundaries.at(-1) || null;
  }
}

class VideoEngine {
  constructor(video, url) { this.video = video; this.video.src = url; }
  play() { return this.video.play(); }
  pause() { this.video.pause(); }
  time() { return this.video.currentTime || 0; }
  on(event, handler) { this.video.addEventListener(event, handler); }
}

class AudioEngine {
  constructor(urls) {
    this.tracks = {};
    Object.entries(urls).forEach(([name, url]) => {
      const audio = new Audio(url);
      audio.loop = true;
      this.tracks[name] = audio;
    });
    this.current = null;
  }
  async switchTo(name, volume) {
    if (this.current === name) {
      if (this.tracks[name]) this.tracks[name].volume = volume;
      return;
    }
    Object.values(this.tracks).forEach((track) => track.pause());
    this.current = name;
    const track = this.tracks[name];
    if (!track) return;
    track.currentTime = 0;
    track.volume = volume;
    await track.play();
  }
  stop() { Object.values(this.tracks).forEach((track) => { track.pause(); track.currentTime = 0; }); }
}

class WledClient {
  constructor(ip) { this.ip = ip; this.connected = false; }
  async probe() {
    const basePath = document.body.dataset.basePath || '';
    const response = await fetch(`${basePath}/api/modules/test`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ esp32_ip: this.ip })
    });
    const data = await response.json();
    this.connected = !!data.ok;
    if (!data.ok) throw new Error(data.message || 'WLED validation failed.');
    return data;
  }
  send(scene) {
    const [r, g, b] = scene.rgb || [0, 0, 0];
    const payload = { on: true, bri: Number(scene.brightness || 0), seg: [{ col: [[r, g, b]], cct: Number(scene.cct || 0) }] };
    return fetch(`http://${this.ip}/json/state`, {
      method: 'POST',
      mode: 'no-cors',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).catch(() => {});
  }
}

class LightingEngine {
  constructor(client) { this.client = client; this.lastKey = ''; }
  apply(scene) {
    const key = JSON.stringify([scene.brightness, scene.cct, scene.rgb]);
    if (key === this.lastKey) return;
    this.lastKey = key;
    this.client.send(scene);
  }
}

class RuntimeController {
  constructor(root) {
    this.root = root;
    this.payload = JSON.parse(root.dataset.module || '{}');
    this.loader = new ModuleLoader(this.payload);
    this.video = new VideoEngine(root.querySelector('[data-runtime-video]') || root.querySelector('video'), this.loader.videoUrl());
    this.timeline = new TimelineEngine(this.loader.timeline());
    this.audio = new AudioEngine(this.loader.audioUrls());
    this.status = root.querySelector('[data-runtime-status]');
    this.sceneIndex = -1;
    this.sessionId = null;
    this.startedAt = null;
  }
  async start() {
    const ip = this.root.dataset.deviceIp || this.root.querySelector('[data-runtime-ip]')?.value.trim();
    if (!/^(25[0-5]|2[0-4]\d|1?\d?\d)(\.(25[0-5]|2[0-4]\d|1?\d?\d)){3}$/.test(ip || '')) {
      this.render('Enter a valid IPv4 address.');
      return;
    }
    this.wled = new WledClient(ip);
    try {
      if (this.root.dataset.mode === 'test') await this.wled.probe();
      if (this.root.dataset.mode === 'tutor') await this.startSession();
      this.lighting = new LightingEngine(this.wled);
      this.startedAt = Date.now();
      this.bind();
      await this.video.play();
    } catch (error) {
      this.render(error.message);
    }
  }
  bind() {
    if (this.bound) return;
    this.bound = true;
    this.video.on('timeupdate', () => this.tick());
    this.video.on('seeked', () => { this.sceneIndex = -1; this.tick(); });
    this.video.on('ended', () => this.end(true));
    this.video.on('pause', () => this.audio.stop());
  }
  async startSession() {
    const basePath = document.body.dataset.basePath || '';
    const response = await fetch(`${basePath}/api/runtime/start`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ assignment_id: this.root.dataset.assignmentId })
    });
    const data = await response.json();
    if (!data.ok) throw new Error(data.message || 'Unable to start playback.');
    this.sessionId = data.session_id;
  }
  tick() {
    const active = this.timeline.sceneAt(this.video.time());
    if (!active) return;
    if (active.index !== this.sceneIndex) {
      this.sceneIndex = active.index;
      this.audio.switchTo(active.scene.audio, Number(active.scene.audio_volume ?? 1)).catch((error) => this.render(error.message));
      this.lighting?.apply(active.scene);
    }
    this.renderStatus(active);
  }
  renderStatus(active) {
    const scene = active.scene;
    this.status.innerHTML = `
      <div><strong>Current Scene</strong><span>${active.index + 1}</span></div>
      <div><strong>Elapsed Time</strong><span>${Math.floor(this.video.time())}s</span></div>
      <div><strong>Current Audio</strong><span>${scene.audio || '-'}</span></div>
      <div><strong>Brightness</strong><span>${scene.brightness}</span></div>
      <div><strong>CCT</strong><span>${scene.cct}</span></div>
      <div><strong>RGB</strong><span>${(scene.rgb || []).join(', ')}</span></div>
      <div><strong>Frequency</strong><span>${scene.frequency || '-'}</span></div>
      <div><strong>Connection</strong><span>${this.wled?.connected ? 'Validated' : 'Sending'}</span></div>`;
  }
  render(message) { if (this.status) this.status.innerHTML = `<p class="empty">${message}</p>`; }
  async end(completed = false) {
    this.video.pause();
    this.audio.stop();
    if (this.root.dataset.mode === 'tutor' && this.sessionId) {
      const basePath = document.body.dataset.basePath || '';
      await fetch(`${basePath}/api/runtime/end`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: this.sessionId,
          assignment_id: this.root.dataset.assignmentId,
          completed,
          duration: this.startedAt ? Math.round((Date.now() - this.startedAt) / 1000) : null
        })
      }).catch(() => {});
      if (completed) window.location.href = `${basePath}/tutor/modules`;
    }
  }
}

document.querySelectorAll('[data-runtime]').forEach((root) => {
  const runtime = new RuntimeController(root);
  root.querySelector('[data-runtime-start]')?.addEventListener('click', () => runtime.start());
  root.querySelector('[data-runtime-stop]')?.addEventListener('click', () => runtime.end(false));
});
