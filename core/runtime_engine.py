import os
import time
import logging
from typing import Optional, Callable, Dict, Any
from core.models import LessonModule, EngineState, NeuroState
from core.module_loader import ModuleLoader
from core.video_engine import VideoEngine
from core.audio_engine import AudioEngine
from core.lighting_engine import LightingEngine
from core.timeline_engine import TimelineEngine

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("RuntimeEngine")

class RuntimeEngine:
    def __init__(
        self,
        video_engine: VideoEngine,
        audio_engine: AudioEngine,
        lighting_engine: LightingEngine
    ) -> None:
        """
        Initializes the master orchestrator.
        """
        self.video_engine: VideoEngine = video_engine
        self.audio_engine: AudioEngine = audio_engine
        self.lighting_engine: LightingEngine = lighting_engine
        
        # Loaded module state
        self.active_module: Optional[LessonModule] = None
        self.timeline_engine: Optional[TimelineEngine] = None
        
        # Runtime states
        self.state: EngineState = EngineState.IDLE
        self.active_neuro_state: Optional[NeuroState] = None
        self.active_scene_index: int = -1
        self._session_start_time: float = 0.0  # wall-clock time when session started
        
        # UI notification callbacks (Observer pattern)
        self._ui_tick_callback: Optional[Callable[[Dict[str, Any]], None]] = None
        self._ui_state_change_callback: Optional[Callable[[EngineState], None]] = None

    def set_ui_callbacks(
        self,
        tick_callback: Callable[[Dict[str, Any]], None],
        state_change_callback: Callable[[EngineState], None]
    ) -> None:
        """Registers listener callbacks to feed playback data back to the UI."""
        self._ui_tick_callback = tick_callback
        self._ui_state_change_callback = state_change_callback

    def _notify_state_change(self) -> None:
        """Helper to safely notify the UI when the player's execution state changes."""
        if self._ui_state_change_callback:
            try:
                self._ui_state_change_callback(self.state)
            except Exception as e:
                logger.error(f"Error executing UI state change callback: {str(e)}")

    def load_module(self, module_dir_path: str) -> LessonModule:
        """Loads and prepares a lesson module. Resets any active session."""
        # Stop any active session first
        self.stop_session()
        
        # Loader parses and validates curriculum configuration
        module = ModuleLoader.load_from_directory(module_dir_path)
        
        self.active_module = module
        self.timeline_engine = TimelineEngine(module.timeline)
        self.state = EngineState.IDLE
        self.active_neuro_state = None
        self.active_scene_index = -1
        
        self._notify_state_change()
        logger.info(f"Engine loaded module successfully: {module.name}")
        return module

    def start_session(self, tk_frame_id: Optional[int] = None) -> None:
        """Starts a curriculum synchronization session from the beginning."""
        if not self.active_module:
            raise ValueError("No curriculum module loaded. Please load a module first.")
            
        logger.info(f"Starting synchronized session for module: {self.active_module.name}")
        
        # Reset internal states
        self.active_neuro_state = None
        self.active_scene_index = -1
        
        # 1. Load the video engine
        self.video_engine.load_video(
            self.active_module.video_path,
            tk_frame_id=tk_frame_id,
            default_duration=self.active_module.duration
        )
        
        # 2. Trigger Playback
        self.state = EngineState.PLAYING
        self._session_start_time = time.time()  # record wall-clock start for grace period
        self._notify_state_change()
        
        self.video_engine.play()
        # The update tick will pick up the current time, resolve the starting scene,
        # and trigger the initial audio and lighting states immediately.

    def pause_session(self) -> None:
        """Pauses the active synchronization session (video and audio overlays)."""
        if self.state != EngineState.PLAYING:
            return
            
        self.state = EngineState.PAUSED
        self._notify_state_change()
        
        self.video_engine.pause()
        self.audio_engine.pause()
        logger.info("Session paused.")

    def resume_session(self) -> None:
        """Resumes a paused session."""
        if self.state != EngineState.PAUSED:
            return
            
        self.state = EngineState.PLAYING
        self._notify_state_change()
        
        self.video_engine.play()
        self.audio_engine.resume()
        logger.info("Session resumed.")

    def stop_session(self) -> None:
        """Stops the active session, resetting video, audio overlay, and active states."""
        if self.state == EngineState.IDLE:
            return
            
        self.state = EngineState.STOPPED
        self._notify_state_change()
        
        self.video_engine.stop()
        self.audio_engine.stop()
        
        self.active_neuro_state = None
        self.active_scene_index = -1
        logger.info("Session stopped.")

    def seek_session(self, seconds: float) -> None:
        """Seeks the authoritative master clock. Updates sub-engines on the next tick."""
        if self.state in (EngineState.PLAYING, EngineState.PAUSED):
            self.video_engine.seek(seconds)
            logger.info(f"Master clock seeked to: {seconds:.1f}s")

    def update_tick(self) -> None:
        """
        The periodic heart beat of the Neurosattva runtime.
        This must be called regularly (e.g. every 100ms) by the UI loop.
        It queries the master clock, maintains synchronizations, and dispatches transitions.
        """
        if not self.active_module or not self.timeline_engine:
            return
            
        if self.state not in (EngineState.PLAYING, EngineState.PAUSED):
            return

        # 1. Fetch current authoritative master time
        curr_time = self.video_engine.get_time()
        duration = self.active_module.duration
        
        # 2. Check for end of playback session
        # Give a 2-second startup grace period so the very first tick(s) don't
        # falsely terminate the session before the video engine is fully rolling.
        wall_elapsed = time.time() - self._session_start_time
        startup_grace = wall_elapsed > 2.0
        if startup_grace and (
            curr_time >= duration
            or (self.state == EngineState.PLAYING and not self.video_engine.is_playing() and curr_time > 1.0)
        ):
            logger.info("Authoritative session timeline complete. Stopping session.")
            self.stop_session()
            return

        # 3. Resolve active scene index and corresponding neuro state
        scene_idx, interval = self.timeline_engine.get_scene_index_and_interval(curr_time)
        
        if scene_idx != -1 and interval is not None:
            new_state = interval.state
            
            # 4. Check for state transition boundaries
            if new_state != self.active_neuro_state or scene_idx != self.active_scene_index:
                logger.info(
                    f"SCENE TRANSITION DETECTED at {curr_time:.2f}s: "
                    f"Scene {self.active_scene_index} ({self.active_neuro_state}) -> "
                    f"Scene {scene_idx} ({new_state})"
                )
                
                # Perform state changes
                self.active_neuro_state = new_state
                self.active_scene_index = scene_idx
                
                # Trigger smooth transitions
                # Resolve module's parent folder directory
                module_dir = os.path.dirname(self.active_module.config_path) if self.active_module else None

                # Audio: Crossfade outgoing and incoming profiles over 5.0 seconds
                self.audio_engine.play_state_audio(new_state.audio_filename, module_dir=module_dir, fade_duration_ms=5000)
                
                # Lighting: Smooth fade over 5.0 seconds
                self.lighting_engine.send_state(new_state.target_brightness, new_state.target_cct)

        # 5. Pack telemetry payload and notify UI
        if self._ui_tick_callback:
            scene_name = f"Scene {self.active_scene_index + 1}: {self.active_neuro_state.value.capitalize()}" if self.active_neuro_state else "Unknown Scene"
            status_payload = {
                "current_time": curr_time,
                "duration": duration,
                "progress": curr_time / duration if duration > 0 else 0.0,
                "current_scene_name": scene_name,
                "active_neuro_state": self.active_neuro_state,
                "audio_profile": self.active_neuro_state.audio_filename if self.active_neuro_state else "None",
                "lighting_brightness": self.active_neuro_state.target_brightness if self.active_neuro_state else 0,
                "lighting_cct": self.active_neuro_state.target_cct if self.active_neuro_state else 0,
            }
            try:
                self._ui_tick_callback(status_payload)
            except Exception as e:
                logger.error(f"Error executing UI tick callback: {str(e)}")
