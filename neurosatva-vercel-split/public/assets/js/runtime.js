/**
 * Neurosattva Synchronization Runtime
 * 
 * Architecture:
 * RuntimeController — orchestrates all engines
 *   ModuleLoader — fetches and validates config
 *   TimelineEngine — calculates active scene from video timestamp
 *   VideoEngine — wraps <video> element, provides master clock
 *   AudioEngine — manages audio cross-fading between scenes
 *   LightingEngine — manages WLED state updates
 *   WLEDClient — HTTP transport to ESP32/WLED device (encapsulated)
 *   StatusHUD — updates the on-screen status display
 */

class WLEDClient {
  constructor(ipAddress) {
    this.ip = ipAddress;
    this.baseUrl = `http://${this.ip}`;
  }

  async _fetch(endpoint, options = {}) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 3000);
    
    try {
      const isHttps = window.location.protocol === 'https:';
      if (isHttps) {
        console.warn('WLEDClient: Page is HTTPS but communicating with HTTP ESP32. Mixed content issues may occur.');
      }
      
      const response = await fetch(`${this.baseUrl}${endpoint}`, {
        ...options,
        mode: 'cors',
        signal: controller.signal
      });
      
      clearTimeout(timeoutId);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return await response.json();
    } catch (err) {
      clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('Connection to WLED device timed out. Check network.');
      } else if (err.message.includes('CORS') || err.message === 'Failed to fetch' || err instanceof TypeError) {
         if(err.message === 'Failed to fetch') {
           throw new Error('Unable to connect to classroom lighting controller. Ensure this computer and ESP32 are on the same Wi-Fi network.');
         }
        throw new Error('CORS error communicating with WLED. Ensure the device allows cross-origin requests. ' + err.message);
      }
      throw err;
    }
  }

  async ping() {
    const data = await this._fetch('/json/info');
    return !!(data && (data.name || data.ver));
  }

  async setScene(scene) {
    const bri = scene.brightness !== undefined ? Math.round(parseFloat(scene.brightness) * 2.55) : 255;
    const rawCct = scene.cct !== undefined && scene.cct !== null ? parseFloat(scene.cct) : 50;

    let cctMireds = 326;
    if (rawCct <= 100) {
      cctMireds = Math.round(153 + (Math.min(100, Math.max(0, rawCct)) / 100) * (500 - 153));
    } else {
      cctMireds = Math.min(500, Math.max(153, Math.round(rawCct)));
    }

    const rgbVal = scene.rgb && Array.isArray(scene.rgb) ? scene.rgb : [255, 255, 255];
    const rgbwVal = [rgbVal[0], rgbVal[1], rgbVal[2], 255];

    const state = {
      on: true,
      bri: bri,
      cct: cctMireds,
      col: [rgbwVal],
      seg: [
        {
          id: 0,
          start: 0,
          stop: 255,
          on: true,
          bri: bri,
          col: [rgbwVal],
          cct: cctMireds
        }
      ]
    };

    return this._fetch('/json/state', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(state)
    });
  }

  async off() {
    return this._fetch('/json/state', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ on: false })
    });
  }
}

class TimelineEngine {
  constructor(timeline) {
    this.scenes = timeline;
  }

  getActiveScene(currentTimeSeconds) {
    let cumulative = 0;
    for (let i = 0; i < this.scenes.length; i++) {
      const scene = this.scenes[i];
      const dur = parseFloat(scene.duration || 0);
      cumulative += dur;
      if (currentTimeSeconds <= cumulative) {
        return scene;
      }
    }
    return this.scenes[this.scenes.length - 1] || null;
  }

  getSceneIndex(currentTimeSeconds) {
    let cumulative = 0;
    for (let i = 0; i < this.scenes.length; i++) {
      const scene = this.scenes[i];
      const dur = parseFloat(scene.duration || 0);
      cumulative += dur;
      if (currentTimeSeconds <= cumulative) {
        return i;
      }
    }
    return this.scenes.length - 1;
  }

  getSceneStartTime(sceneIndex) {
    let start = 0;
    for (let i = 0; i < sceneIndex && i < this.scenes.length; i++) {
      start += parseFloat(this.scenes[i].duration || 0);
    }
    return start;
  }

  getTotalDuration() {
    return this.scenes.reduce((acc, scene) => acc + parseFloat(scene.duration || 0), 0);
  }
}

class AudioEngine {
  constructor() {
    this.ctx = null;
    this.currentUrl = null;
    this.currentAudio = null;
    this.sourceNode = null;
    this.masterGain = null;
    this.modulatorOsc1 = null;
    this.modulatorOsc2 = null;
    this.modulatorGain = null;
    this.mergerNode = null;
    this.currentModulation = 'None';
    this.currentFrequency = 0;
  }

  initContext() {
    if (!this.ctx) {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (AudioCtx) {
        this.ctx = new AudioCtx();
      }
    }
    if (this.ctx && this.ctx.state === 'suspended') {
      this.ctx.resume().catch(() => {});
    }
  }

  parseFreq(val, defaultFreq = 6) {
    if (typeof val === 'number') return val;
    if (!val) return defaultFreq;
    const num = parseFloat(String(val).replace(/[^0-9.]/g, ''));
    return isNaN(num) || num <= 0 ? defaultFreq : num;
  }

  async loadAudio(url) {
    return new Promise((resolve) => {
      if (!url) return resolve(null);
      const audio = new Audio(url);
      audio.crossOrigin = "anonymous";

      if (audio.readyState >= 2) {
        return resolve(audio);
      }

      let timeoutId = setTimeout(() => {
        cleanup();
        resolve(audio);
      }, 4000);

      const onCanPlay = () => {
        cleanup();
        resolve(audio);
      };
      const onError = () => {
        cleanup();
        resolve(null);
      };
      const cleanup = () => {
        clearTimeout(timeoutId);
        audio.removeEventListener('canplay', onCanPlay);
        audio.removeEventListener('canplaythrough', onCanPlay);
        audio.removeEventListener('loadeddata', onCanPlay);
        audio.removeEventListener('error', onError);
      };

      audio.addEventListener('canplay', onCanPlay);
      audio.addEventListener('canplaythrough', onCanPlay);
      audio.addEventListener('loadeddata', onCanPlay);
      audio.addEventListener('error', onError);
      audio.load();
    }).catch(err => {
      console.error(err);
      return null;
    });
  }

  cleanModulationNodes() {
    if (this.modulatorOsc1) {
      try { this.modulatorOsc1.stop(); } catch (e) {}
      try { this.modulatorOsc1.disconnect(); } catch (e) {}
      this.modulatorOsc1 = null;
    }
    if (this.modulatorOsc2) {
      try { this.modulatorOsc2.stop(); } catch (e) {}
      try { this.modulatorOsc2.disconnect(); } catch (e) {}
      this.modulatorOsc2 = null;
    }
    if (this.modulatorGain) {
      try { this.modulatorGain.disconnect(); } catch (e) {}
      this.modulatorGain = null;
    }
    if (this.mergerNode) {
      try { this.mergerNode.disconnect(); } catch (e) {}
      this.mergerNode = null;
    }
  }

  setupModulation(modulation, frequency, volume) {
    this.initContext();
    if (!this.ctx) return;

    this.cleanModulationNodes();
    const targetHz = this.parseFreq(frequency, 6);
    const rawMod = (modulation || 'None').trim().toLowerCase();

    if (this.masterGain) {
      this.masterGain.gain.setValueAtTime(volume, this.ctx.currentTime);
    }

    if (rawMod.includes('amplitude') || rawMod === 'am') {
      // Amplitude Modulation (AM) — smooth periodic volume modulation
      const lfo = this.ctx.createOscillator();
      const lfoGain = this.ctx.createGain();
      lfo.type = 'sine';
      lfo.frequency.setValueAtTime(targetHz, this.ctx.currentTime);

      const depth = 0.35;
      lfoGain.gain.setValueAtTime(depth * volume, this.ctx.currentTime);

      lfo.connect(lfoGain);
      if (this.masterGain) {
        lfoGain.connect(this.masterGain.gain);
      }
      lfo.start();

      this.modulatorOsc1 = lfo;
      this.modulatorGain = lfoGain;

    } else if (rawMod.includes('isochronic')) {
      // Isochronic Pulse — crisp rhythmic pulses using square wave
      const lfo = this.ctx.createOscillator();
      const lfoGain = this.ctx.createGain();
      lfo.type = 'square';
      lfo.frequency.setValueAtTime(targetHz, this.ctx.currentTime);

      const depth = 0.5;
      lfoGain.gain.setValueAtTime(depth * volume, this.ctx.currentTime);

      lfo.connect(lfoGain);
      if (this.masterGain) {
        lfoGain.connect(this.masterGain.gain);
      }
      lfo.start();

      this.modulatorOsc1 = lfo;
      this.modulatorGain = lfoGain;

    } else if (rawMod.includes('tremolo') || rawMod.includes('slow am')) {
      // Tremolo / Slow AM — gentle volume oscillation (max 3 Hz)
      const slowHz = Math.min(targetHz, 3);
      const lfo = this.ctx.createOscillator();
      const lfoGain = this.ctx.createGain();
      lfo.type = 'sine';
      lfo.frequency.setValueAtTime(slowHz, this.ctx.currentTime);

      const depth = 0.25;
      lfoGain.gain.setValueAtTime(depth * volume, this.ctx.currentTime);

      lfo.connect(lfoGain);
      if (this.masterGain) {
        lfoGain.connect(this.masterGain.gain);
      }
      lfo.start();

      this.modulatorOsc1 = lfo;
      this.modulatorGain = lfoGain;

    } else if (rawMod.includes('monaural')) {
      // Monaural Beat — two pure acoustic tones mixed together into audio stream
      const carrierHz = 200;
      const osc1 = this.ctx.createOscillator();
      const osc2 = this.ctx.createOscillator();
      const toneGain = this.ctx.createGain();

      osc1.type = 'sine';
      osc1.frequency.setValueAtTime(carrierHz, this.ctx.currentTime);

      osc2.type = 'sine';
      osc2.frequency.setValueAtTime(carrierHz + targetHz, this.ctx.currentTime);

      toneGain.gain.setValueAtTime(0.0, this.ctx.currentTime);

      osc1.connect(toneGain);
      osc2.connect(toneGain);
      toneGain.connect(this.ctx.destination);

      osc1.start();
      osc2.start();

      this.modulatorOsc1 = osc1;
      this.modulatorOsc2 = osc2;
      this.modulatorGain = toneGain;

    } else if (rawMod.includes('binaural')) {
      // Binaural Beat — Left/Right frequency differential
      const carrierHz = 200;
      const oscL = this.ctx.createOscillator();
      const oscR = this.ctx.createOscillator();
      const merger = this.ctx.createChannelMerger(2);
      const toneGain = this.ctx.createGain();

      oscL.type = 'sine';
      oscL.frequency.setValueAtTime(carrierHz, this.ctx.currentTime);

      oscR.type = 'sine';
      oscR.frequency.setValueAtTime(carrierHz + targetHz, this.ctx.currentTime);

      toneGain.gain.setValueAtTime(0.0, this.ctx.currentTime);

      oscL.connect(merger, 0, 0);
      oscR.connect(merger, 0, 1);
      merger.connect(toneGain);
      toneGain.connect(this.ctx.destination);

      oscL.start();
      oscR.start();

      this.modulatorOsc1 = oscL;
      this.modulatorOsc2 = oscR;
      this.mergerNode = merger;
      this.modulatorGain = toneGain;
    }
  }

  async switchTo(url, volume = 1, modulation = 'None', frequency = '6', fadeMs = 300) {
    this.initContext();

    if (typeof url === 'object' && url !== null) {
      const scene = url;
      url = scene._audio_url;
      volume = scene.audio_volume !== undefined ? scene.audio_volume : 1;
      modulation = scene.modulation || 'None';
      frequency = scene.frequency || '6';
    }

    if (!url) {
      this.stop();
      return;
    }

    const modType = modulation || 'None';
    const targetHz = frequency || '6';

    if (this.currentUrl === url && this.currentModulation === modType && this.currentFrequency === targetHz) {
      if (this.masterGain && this.ctx) {
        this.masterGain.gain.setValueAtTime(volume, this.ctx.currentTime);
      } else if (this.currentAudio) {
        this.currentAudio.volume = volume;
      }
      return;
    }

    const newAudio = await this.loadAudio(url);
    if (!newAudio) return;

    newAudio.loop = true;

    if (this.currentAudio) {
      this.currentAudio.pause();
    }

    this.currentUrl = url;
    this.currentAudio = newAudio;
    this.currentModulation = modType;
    this.currentFrequency = targetHz;

    if (this.ctx) {
      try {
        if (this.sourceNode) {
          try { this.sourceNode.disconnect(); } catch(e){}
        }
        this.sourceNode = this.ctx.createMediaElementSource(this.currentAudio);
        this.masterGain = this.ctx.createGain();
        this.masterGain.gain.setValueAtTime(volume, this.ctx.currentTime);

        this.sourceNode.connect(this.masterGain);
        this.masterGain.connect(this.ctx.destination);

        this.setupModulation(modType, targetHz, volume);
      } catch (err) {
        console.warn("Web Audio API routing fallback", err);
        this.currentAudio.volume = volume;
      }
    } else {
      this.currentAudio.volume = volume;
    }

    try {
      await this.currentAudio.play();
    } catch(e) {
      console.error("Audio playback failed", e);
    }
  }

  syncTime(elapsedSeconds, isVideoPlaying) {
    if (this.currentAudio && this.currentAudio.duration > 0) {
      if (isVideoPlaying && this.currentAudio.paused) {
        this.currentAudio.play().catch(e => console.error("Audio auto-resume on sync failed", e));
      } else if (!isVideoPlaying && !this.currentAudio.paused) {
        this.currentAudio.pause();
      }
      const targetTime = elapsedSeconds % this.currentAudio.duration;
      if (Math.abs(this.currentAudio.currentTime - targetTime) > 0.4) {
        try {
          this.currentAudio.currentTime = targetTime;
        } catch (e) {}
      }
    }
  }

  pause() {
    if (this.currentAudio) this.currentAudio.pause();
    if (this.ctx && this.ctx.state === 'running') this.ctx.suspend();
  }

  resume() {
    this.initContext();
    if (this.currentAudio) this.currentAudio.play().catch(e => console.error(e));
  }

  stop() {
    this.cleanModulationNodes();
    if (this.currentAudio) {
      this.currentAudio.pause();
      this.currentAudio = null;
    }
    if (this.sourceNode) {
      try { this.sourceNode.disconnect(); } catch(e){}
      this.sourceNode = null;
    }
    if (this.masterGain) {
      try { this.masterGain.disconnect(); } catch(e){}
      this.masterGain = null;
    }
    this.currentUrl = null;
    this.currentModulation = 'None';
    this.currentFrequency = 0;
  }
}

class VideoEngine {
  constructor(videoElement) {
    this.video = videoElement;
  }

  async load(url) {
    return new Promise((resolve) => {
      if (!url) {
        resolve();
        return;
      }
      this.video.src = url;

      if (this.video.readyState >= 1) {
        resolve();
        return;
      }

      let timeoutId = setTimeout(() => {
        cleanup();
        resolve();
      }, 4000);

      const onCanPlay = () => {
        cleanup();
        resolve();
      };
      const onError = (e) => {
        cleanup();
        console.warn('Video stream warning for:', url, e);
        resolve();
      };
      const cleanup = () => {
        clearTimeout(timeoutId);
        this.video.removeEventListener('canplay', onCanPlay);
        this.video.removeEventListener('loadeddata', onCanPlay);
        this.video.removeEventListener('loadedmetadata', onCanPlay);
        this.video.removeEventListener('error', onError);
      };

      this.video.addEventListener('canplay', onCanPlay);
      this.video.addEventListener('loadeddata', onCanPlay);
      this.video.addEventListener('loadedmetadata', onCanPlay);
      this.video.addEventListener('error', onError);
      this.video.load();
    });
  }

  play() {
    return this.video.play();
  }

  pause() {
    this.video.pause();
  }

  resume() {
    return this.video.play();
  }

  get currentTime() { return this.video.currentTime; }
  get duration() { return this.video.duration; }
  get ended() { return this.video.ended; }

  onTimeUpdate(callback) { this.video.addEventListener('timeupdate', callback); }
  onEnded(callback) { this.video.addEventListener('ended', callback); }
  onError(callback) { this.video.addEventListener('error', callback); }
}

class LightingEngine {
  constructor(wledClient) {
    this.client = wledClient;
    this.isConnected = false;
    this.lastError = null;
    this.lastSceneJson = null;
    this.appliedSceneIdx = -1;
    this.isPending = false;
    this.lastAttemptTime = 0;
  }

  async applyScene(scene, sceneIdx) {
    if (!scene) return;
    const sceneJson = JSON.stringify({
      rgb: scene.rgb,
      brightness: scene.brightness,
      cct: scene.cct
    });
    
    if (this.appliedSceneIdx === sceneIdx && this.lastSceneJson === sceneJson && this.isConnected) return;

    const now = Date.now();
    if (this.isPending) return;
    if (!this.isConnected && (now - this.lastAttemptTime < 2500)) return;

    this.isPending = true;
    this.lastAttemptTime = now;

    try {
      await this.client.setScene(scene);
      this.isConnected = true;
      this.lastError = null;
      this.lastSceneJson = sceneJson;
      this.appliedSceneIdx = sceneIdx;
      document.dispatchEvent(new CustomEvent('wled-success'));
    } catch (err) {
      this.isConnected = false;
      this.lastError = err.message;
      document.dispatchEvent(new CustomEvent('wled-error', { detail: err.message }));
    } finally {
      this.isPending = false;
    }
  }

  async turnOff() {
    try {
      await this.client.off();
      this.isConnected = true;
      this.lastError = null;
    } catch (err) {
      this.isConnected = false;
      this.lastError = err.message;
    }
  }
}

class StatusHUD {
  constructor(hudElement) {
    this.hud = hudElement;
    if (!this.hud) return;
    this.els = {
      scene: document.getElementById('hud-scene'),
      elapsed: document.getElementById('hud-elapsed'),
      audio: document.getElementById('hud-audio'),
      brightness: document.getElementById('hud-brightness'),
      cct: document.getElementById('hud-cct'),
      rgb: document.getElementById('hud-rgb'),
      rgbSwatch: document.getElementById('hud-rgb-swatch'),
      connDot: document.getElementById('hud-connection-dot'),
      connText: document.getElementById('hud-connection-text'),
      errorBanner: document.getElementById('hud-error-banner')
    };
  }

  update(data) {
    if (!this.hud) return;
    
    if (this.els.scene && data.scene !== undefined) this.els.scene.textContent = data.scene;
    
    if (this.els.elapsed && data.elapsed !== undefined) {
      const s = Math.floor(data.elapsed);
      this.els.elapsed.textContent = Math.floor(s/60) + ':' + String(s%60).padStart(2,'0');
    }
    
    if (this.els.audio && data.audio !== undefined) this.els.audio.textContent = data.audio;
    if (this.els.brightness && data.brightness !== undefined) this.els.brightness.textContent = data.brightness + '%';
    if (this.els.cct && data.cct !== undefined) this.els.cct.textContent = data.cct + 'K';
    
    if (this.els.rgb && data.rgb !== undefined) {
      this.els.rgb.textContent = data.rgb.join(', ');
    }
    if (this.els.rgbSwatch && data.rgb !== undefined) {
      this.els.rgbSwatch.style.backgroundColor = `rgb(${data.rgb.join(',')})`;
    }

    if (data.connectionStatus) {
      if (this.els.connDot) {
        this.els.connDot.className = 'connection-dot ' + data.connectionStatus;
      }
      if (this.els.connText) {
        this.els.connText.textContent = data.connectionText;
      }
    }
  }

  setError(message) {
    if (this.els.errorBanner) {
      this.els.errorBanner.textContent = message;
      this.els.errorBanner.classList.add('visible');
    }
  }

  clearError() {
    if (this.els.errorBanner) {
      this.els.errorBanner.classList.remove('visible');
    }
  }
}

class RuntimeController {
  constructor(options) {
    this.config = options.config;
    this.videoElement = options.videoElement;
    this.hudElement = options.hudElement;
    this.onEnd = options.onEnd || (() => {});
    this.testMode = options.testMode || false;
    
    this.ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
    this.tickInterval = null;
    this.activeSceneIndex = -1;
    this.startTime = 0;
    this.pauseOffset = 0;
    this.isPaused = false;
    this.lastPauseTime = 0;
  }

  async init() {
    if (!this.config._esp32_ip || !this.ipRegex.test(this.config._esp32_ip)) {
      throw new Error("Invalid ESP32 IP address format.");
    }

    this.wled = new WLEDClient(this.config._esp32_ip);
    this.hud = new StatusHUD(this.hudElement);
    this.timeline = new TimelineEngine(this.config.timeline || []);
    this.video = new VideoEngine(this.videoElement);
    this.audio = new AudioEngine();
    this.lighting = new LightingEngine(this.wled);

    this.hud.update({ connectionStatus: 'connecting', connectionText: 'Connecting...' });

    const isAlive = await this.wled.ping();
    if (!isAlive) {
      this.hud.update({ connectionStatus: 'disconnected', connectionText: 'Disconnected' });
      throw new Error("Unable to connect to WLED device at " + this.config._esp32_ip);
    }

    this.hud.update({ connectionStatus: 'connected', connectionText: 'Connected' });

    if (this.config._video_url) {
      try {
        await this.video.load(this.config._video_url);
      } catch (err) {
        console.warn("Video asset load warning:", err);
      }
    }

    document.addEventListener('wled-error', (e) => {
      this.hud.update({ connectionStatus: 'disconnected', connectionText: 'Disconnected' });
      this.hud.setError(e.detail);
    });
    
    document.addEventListener('wled-success', () => {
      this.hud.clearError();
      this.hud.update({ connectionStatus: 'connected', connectionText: 'Connected' });
    });
  }

  get currentTime() {
    let t = this.videoElement ? this.videoElement.currentTime : 0;
    const fallbackTime = Math.max(0, (Date.now() - this.startTime - this.pauseOffset) / 1000);
    if ((t <= 0.05 || isNaN(t)) && fallbackTime > 0) {
      t = fallbackTime;
    }
    return t;
  }

  async start() {
    this.startTime = Date.now();
    this.pauseOffset = 0;
    this.isPaused = false;
    this.audio.initContext();

    try {
      await this.video.play();
    } catch (err) {
      console.warn("Primary video.play() failed, attempting muted fallback playback:", err);
      try {
        this.videoElement.muted = true;
        await this.video.play();
      } catch (err2) {
        console.warn("Video playback unavailable on this browser/codec, running session audio & lighting clock:", err2);
      }
    }

    if (!this.tickInterval) {
      this.tickInterval = setInterval(this._tick.bind(this), 250);
    }
  }

  pause() {
    if (this.isPaused) return;
    this.isPaused = true;
    this.lastPauseTime = Date.now();
    this.video.pause();
    this.audio.pause();
  }

  resume() {
    if (!this.isPaused) return;
    this.isPaused = false;
    if (this.lastPauseTime > 0) {
      this.pauseOffset += (Date.now() - this.lastPauseTime);
    }
    this.video.resume();
    this.audio.resume();
  }

  async stop(error = null) {
    if (this.tickInterval) clearInterval(this.tickInterval);
    this.video.pause();
    this.audio.stop();
    await this.lighting.turnOff();
    
    this.onEnd({ completed: !error, error });
  }

  _tick() {
    if (this.isPaused) return;

    let t = this.video.currentTime;
    const fallbackTime = Math.max(0, (Date.now() - this.startTime - this.pauseOffset) / 1000);
    if ((t <= 0.05 || isNaN(t)) && fallbackTime > 0) {
      t = fallbackTime;
    }

    const totalDuration = this.timeline.getTotalDuration();
    if (totalDuration > 0 && t >= totalDuration) {
      this.stop();
      return;
    }

    if (this.video.ended) {
      this.stop();
      return;
    }

    const scene = this.timeline.getActiveScene(t);
    const sceneIdx = this.timeline.getSceneIndex(t);
    const sceneStart = this.timeline.getSceneStartTime(sceneIdx);
    const sceneElapsed = Math.max(0, t - sceneStart);

    if (scene) {
      this.lighting.applyScene(scene, sceneIdx);

      if (sceneIdx !== this.activeSceneIndex) {
        this.activeSceneIndex = sceneIdx;
        
        if (scene._audio_url) {
          this.audio.switchTo(scene._audio_url, scene.audio_volume !== undefined ? scene.audio_volume : 1, scene.modulation, scene.frequency);
        } else {
          this.audio.stop();
        }
      }
    }

    if (this.audio) {
      this.audio.syncTime(sceneElapsed, !this.isPaused);
    }

    let audioDisplayName = '-';
    if (scene) {
      if (scene.audio) {
        audioDisplayName = scene.audio;
      } else if (scene._audio_url) {
        const match = scene._audio_url.match(/file=([^&]+)/);
        audioDisplayName = match ? decodeURIComponent(match[1]) : scene._audio_url.split('/').pop();
      }
    }

    this.hud.update({
      scene: `Scene ${this.activeSceneIndex + 1}`,
      elapsed: t,
      audio: audioDisplayName,
      brightness: scene ? scene.brightness : 0,
      cct: scene ? scene.cct : 0,
      rgb: scene ? scene.rgb : [0,0,0]
    });
  }
}

window.NeurosattvaRuntime = {
  RuntimeController,
  WLEDClient,
  TimelineEngine,
  AudioEngine,
  VideoEngine,
  LightingEngine,
  StatusHUD,
};
