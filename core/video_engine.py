import time
import logging
from typing import Optional, Callable, Any

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("VideoEngine")

# Attempt VLC import
VLC_AVAILABLE = False
vlc = None  # type: ignore
try:
    import vlc as vlc  # type: ignore
    # Basic test initialization to check library bindings
    _test_instance = vlc.Instance()
    del _test_instance
    VLC_AVAILABLE = True
except Exception as e:
    logger.warning(
        f"VLC media player components not found or failed to load. "
        f"Enabling Simulation Fallback Engine. Error details: {str(e)}"
    )

class VideoEngine:
    def __init__(self, use_simulation_fallback: bool = True) -> None:
        """
        Initializes the video engine.
        If VLC is installed, uses python-vlc.
        Otherwise, if use_simulation_fallback is True, falls back to a high-fidelity simulation engine.
        """
        self.vlc_available: bool = VLC_AVAILABLE
        self.use_fallback: bool = not VLC_AVAILABLE and use_simulation_fallback
        
        # VLC instances (typed as Any to gracefully support no-VLC environments)
        self.instance: Optional[Any] = None
        self.media_player: Optional[Any] = None
        
        # State tracking
        self.video_path: Optional[str] = None
        self.duration: float = 0.0  # seconds
        self._playing: bool = False
        
        # Simulation Mode fields
        self._sim_current_time: float = 0.0
        self._sim_last_update: float = 0.0
        
        if self.vlc_available:
            self._initialize_vlc()
        else:
            logger.info("Initializing Video Engine in SIMULATION (Fallback) mode.")

    def _initialize_vlc(self) -> None:
        """Initializes the VLC player instance."""
        try:
            # Silence default VLC logs to keep clean console output
            self.instance = vlc.Instance("--quiet", "--no-video-title-show")
            self.media_player = self.instance.media_player_new()
            logger.info("VLC Engine initialized successfully.")
        except Exception as e:
            logger.error(f"Failed to instantiate VLC player: {str(e)}. Falling back to simulation.")
            self.vlc_available = False
            self.use_fallback = True

    def load_video(self, video_path: str, tk_frame_id: Optional[int] = None, default_duration: float = 300.0) -> bool:
        """
        Loads a video file.
        tk_frame_id: The Windows winfo_id() of the Tkinter frame where video should render.
        default_duration: Duration to fall back to in Simulation mode (in seconds).
        """
        self.video_path = video_path
        self._playing = False
        
        if not self.use_fallback:
            try:
                media = self.instance.media_new(video_path)
                self.media_player.set_media(media)
                
                # If frame ID is provided, embed VLC video display on Windows
                if tk_frame_id is not None:
                    self.media_player.set_hwnd(tk_frame_id)
                    logger.info(f"Embedded VLC video output on HWND handle: {tk_frame_id}")
                
                # Fetch video duration from VLC (forces player to parse media)
                self.media_player.play()
                time.sleep(0.1)  # Brief pause to allow VLC to read media length
                self.media_player.stop()
                
                length_ms = self.media_player.get_length()
                if length_ms > 0:
                    self.duration = length_ms / 1000.0
                else:
                    self.duration = default_duration
                
                logger.info(f"VLC successfully loaded video: {video_path} (Duration: {self.duration:.1f}s)")
                return True
            except Exception as e:
                logger.error(f"VLC failed to load video '{video_path}': {str(e)}. Switching to simulation fallback.")
                self.use_fallback = True
                
        # Simulation Mode loading
        if self.use_fallback:
            self.duration = default_duration
            self._sim_current_time = 0.0
            self._sim_last_update = time.time()
            logger.info(f"Simulation engine loaded video path: {video_path} (Simulated Duration: {self.duration:.1f}s)")
            return True
            
        return False

    def play(self) -> None:
        """Starts or resumes video playback."""
        self._playing = True
        if not self.use_fallback:
            self.media_player.play()
            logger.info("VLC playback started.")
        else:
            self._sim_last_update = time.time()
            logger.info("Simulation playback started.")

    def pause(self) -> None:
        """Pauses video playback."""
        self._playing = False
        if not self.use_fallback:
            # Explicitly pause instead of toggling to avoid race conditions
            self.media_player.set_pause(1)
            logger.info("VLC playback paused.")
        else:
            self._update_simulation_time()
            logger.info("Simulation playback paused.")

    def stop(self) -> None:
        """Stops video playback and resets current position."""
        self._playing = False
        if not self.use_fallback:
            self.media_player.stop()
            logger.info("VLC playback stopped.")
        else:
            self._sim_current_time = 0.0
            logger.info("Simulation playback stopped.")

    def seek(self, seconds: float) -> None:
        """Seeks to a specific position in seconds."""
        target = max(0.0, min(seconds, self.duration))
        if not self.use_fallback:
            self.media_player.set_time(int(target * 1000))
            logger.info(f"VLC seeked to: {target:.1f}s")
        else:
            self._sim_current_time = target
            self._sim_last_update = time.time()
            logger.info(f"Simulation seeked to: {target:.1f}s")

    def get_time(self) -> float:
        """Returns the current playback position in seconds."""
        if not self._playing:
            if not self.use_fallback:
                val = self.media_player.get_time()
                return max(0.0, val / 1000.0) if val > 0 else 0.0
            return self._sim_current_time
            
        if not self.use_fallback:
            val = self.media_player.get_time()
            if val > 0:
                # Synchronize duration if VLC was late reading it
                dur_ms = self.media_player.get_length()
                if dur_ms > 0:
                    self.duration = dur_ms / 1000.0
                return val / 1000.0
            return 0.0
        else:
            self._update_simulation_time()
            return self._sim_current_time

    def get_duration(self) -> float:
        """Returns the total video duration in seconds."""
        return self.duration

    def is_playing(self) -> bool:
        """Checks if the video is currently playing."""
        if not self.use_fallback:
            # Query the hardware state of the media player
            state = self.media_player.get_state()
            return state == vlc.State.Playing
        return self._playing

    def set_volume(self, volume: float) -> None:
        """Sets the audio volume of the video player (0.0 to 1.0)."""
        if not self.use_fallback:
            vlc_vol = int(max(0.0, min(1.0, volume)) * 100)
            self.media_player.audio_set_volume(vlc_vol)
            logger.info(f"VLC video audio volume updated to: {vlc_vol}%")

    def _update_simulation_time(self) -> None:
        """Advances the simulation timer relative to real elapsed wall time."""
        if not self._playing:
            return
            
        now = time.time()
        elapsed = now - self._sim_last_update
        self._sim_current_time += elapsed
        self._sim_last_update = now
        
        # Loop or stop if end of duration is reached
        if self._sim_current_time >= self.duration:
            self._sim_current_time = self.duration
            self._playing = False
            logger.info("Simulation reached end of duration.")
