import os
import logging
from typing import Dict, Optional
import pygame

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("AudioEngine")

class AudioEngine:
    def __init__(self, assets_dir: str, default_volume: float = 0.3) -> None:
        """
        Initializes the Pygame Audio Engine.
        assets_dir: Path to directory containing focus.mp3, creative.mp3, calm.mp3.
        default_volume: Volume level (0.0 to 1.0) for the neuro-audio overlays.
        """
        self.assets_dir: str = assets_dir
        self.overlay_volume: float = default_volume
        self.is_initialized: bool = False
        
        # Audio asset caches
        self._sound_cache: Dict[str, pygame.mixer.Sound] = {}
        
        # Crossfade channels
        self.channel_a: Optional[pygame.mixer.Channel] = None
        self.channel_b: Optional[pygame.mixer.Channel] = None
        self.active_channel: Optional[pygame.mixer.Channel] = None
        
        self.current_sound_name: Optional[str] = None
        
        self._initialize_mixer()

    def _initialize_mixer(self) -> None:
        """Initializes Pygame and its mixer, setting up audio channels."""
        try:
            pygame.mixer.init(frequency=44100, size=-16, channels=2, buffer=4096)
            # Allocate static channels for crossfading
            self.channel_a = pygame.mixer.Channel(0)
            self.channel_b = pygame.mixer.Channel(1)
            
            # Apply initial volume
            self.channel_a.set_volume(self.overlay_volume)
            self.channel_b.set_volume(self.overlay_volume)
            
            self.active_channel = self.channel_a
            self.is_initialized = True
            logger.info("Pygame mixer initialized successfully.")
        except pygame.error as e:
            logger.error(f"Pygame mixer initialization failed (no audio device?): {str(e)}")
            self.is_initialized = False
        except Exception as e:
            logger.error(f"Unexpected error initializing Pygame mixer: {str(e)}")
            self.is_initialized = False

    def load_sound(self, filename: str, module_dir: Optional[str] = None) -> Optional[pygame.mixer.Sound]:
        """
        Loads an audio file into cache. Looks in module_dir first (with common aliases)
        before falling back to the global assets directory.
        Returns the Sound object or None on failure.
        """
        if not self.is_initialized:
            return None
            
        # Determine unique cache key based on filename and optional module_dir
        cache_key = f"{module_dir}:{filename}" if module_dir else filename
        if cache_key in self._sound_cache:
            return self._sound_cache[cache_key]
            
        # Set up search candidates (aliases) to match user assets dynamically
        candidates = [filename]
        if filename == "focus.mp3":
            candidates.extend(["focus.mp3", "focus_alert.mp3"])
        elif filename == "creative.mp3":
            candidates.extend(["nature.mp3", "creative.mp3", "creative_active.mp3"])
        elif filename == "calm.mp3":
            candidates.extend(["relaxation.mp3", "pink_noise.mp3", "calm.mp3", "calm_absorb.mp3"])

        file_path = None
        
        # 1. Search in curriculum module directory (highest priority)
        if module_dir and os.path.isdir(module_dir):
            for cand in candidates:
                cand_path = os.path.join(module_dir, cand)
                if os.path.isfile(cand_path):
                    file_path = cand_path
                    logger.info(f"Discovered local module audio: '{cand}'")
                    break

        # 2. Search in global assets directory (fallback)
        if not file_path:
            for cand in candidates:
                cand_path = os.path.join(self.assets_dir, cand)
                if os.path.isfile(cand_path):
                    file_path = cand_path
                    logger.info(f"Discovered global fallback audio: '{cand}'")
                    break

        if not file_path:
            logger.warning(f"Neuro-audio asset file not found (tried standard '{filename}' and aliases: {candidates[1:]})")
            return None
            
        try:
            sound = pygame.mixer.Sound(file_path)
            self._sound_cache[cache_key] = sound
            logger.info(f"Successfully loaded and cached sound: {os.path.basename(file_path)}")
            return sound
        except pygame.error as e:
            logger.error(f"Failed to load audio asset '{file_path}': {str(e)}")
            return None

    def play_state_audio(self, filename: str, module_dir: Optional[str] = None, fade_duration_ms: int = 5000) -> None:
        """
        Transitions to a new state audio file. If the file is already playing, does nothing.
        If a new file is specified, performs a smooth crossfade. Searches locally first.
        """
        if not self.is_initialized:
            return
            
        if self.current_sound_name == filename:
            # Already playing this exact neuro state profile, no transition needed
            return
            
        sound = self.load_sound(filename, module_dir)
        if not sound:
            logger.warning(f"Could not transition audio to '{filename}' due to missing or invalid file.")
            return

        # Determine outgoing and incoming channels
        if self.active_channel == self.channel_a:
            outgoing = self.channel_a
            incoming = self.channel_b
        else:
            outgoing = self.channel_b
            incoming = self.channel_a

        try:
            # 1. Fade out outgoing channel
            if outgoing.get_busy():
                outgoing.fadeout(fade_duration_ms)
                logger.info("Fading out outgoing audio channel.")
                
            # 2. Fade in incoming channel (loops=-1 for infinite looping)
            incoming.set_volume(self.overlay_volume)
            incoming.play(sound, loops=-1, fade_ms=fade_duration_ms)
            logger.info(f"Fading in incoming audio asset '{filename}' over {fade_duration_ms}ms.")
            
            # Update pointers
            self.active_channel = incoming
            self.current_sound_name = filename
            
        except pygame.error as e:
            logger.error(f"Pygame error during audio transition crossfade: {str(e)}")


    def set_volume(self, volume: float) -> None:
        """Sets the volume of both neuro-audio channels (0.0 to 1.0)."""
        self.overlay_volume = max(0.0, min(1.0, volume))
        if self.is_initialized:
            if self.channel_a:
                self.channel_a.set_volume(self.overlay_volume)
            if self.channel_b:
                self.channel_b.set_volume(self.overlay_volume)
            logger.info(f"Audio overlay volume updated to: {self.overlay_volume}")

    def pause(self) -> None:
        """Pauses all neuro audio overlays."""
        if self.is_initialized:
            if self.channel_a:
                self.channel_a.pause()
            if self.channel_b:
                self.channel_b.pause()
            logger.info("Audio overlay paused.")

    def resume(self) -> None:
        """Resumes playing neuro audio overlays."""
        if self.is_initialized:
            if self.channel_a:
                self.channel_a.unpause()
            if self.channel_b:
                self.channel_b.unpause()
            logger.info("Audio overlay resumed.")

    def stop(self) -> None:
        """Stops all active audio channels and resets status."""
        if self.is_initialized:
            if self.channel_a:
                self.channel_a.stop()
            if self.channel_b:
                self.channel_b.stop()
            self.current_sound_name = None
            logger.info("Audio overlay stopped.")
