import os
import sys
import logging
import pygame
import customtkinter as ctk

from core.video_engine import VideoEngine
from core.audio_engine import AudioEngine
from core.lighting_engine import LightingEngine
from core.runtime_engine import RuntimeEngine
from ui.main_window import MainWindow

# Configure global logger
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("NeurosattvaMain")

def main() -> None:
    logger.info("Initializing Neurosattva Curriculum Engine...")
    
    # 1. Resolve absolute system directories
    base_dir = os.path.dirname(os.path.abspath(__file__))
    
    assets_audio_dir = os.path.join(base_dir, "assets", "audio")
    settings_file_path = os.path.join(base_dir, "config", "settings.json")
    
    # Ensure assets directory exists for system audio
    os.makedirs(assets_audio_dir, exist_ok=True)
    os.makedirs(os.path.dirname(settings_file_path), exist_ok=True)

    # 2. Instantiate individual subsystems
    logger.info(f"Loading Audio Engine (assets from: {assets_audio_dir})...")
    audio_engine = AudioEngine(assets_dir=assets_audio_dir, default_volume=0.3)

    logger.info("Loading Video Engine (VLC wrapper)...")
    video_engine = VideoEngine(use_simulation_fallback=True)

    logger.info("Loading Lighting Engine (WLED API worker)...")
    lighting_engine = LightingEngine(wled_ip="192.168.1.100", transition_ds=50)

    # 3. Instantiate system orchestrator
    logger.info("Loading Runtime Engine Orchestrator...")
    runtime_engine = RuntimeEngine(
        video_engine=video_engine,
        audio_engine=audio_engine,
        lighting_engine=lighting_engine
    )

    # 4. Initialize CustomTkinter GUI Window
    logger.info("Assembling Telemetry Desktop Interface...")
    app = MainWindow(
        video_engine=video_engine,
        audio_engine=audio_engine,
        lighting_engine=lighting_engine,
        runtime_engine=runtime_engine,
        settings_path=settings_file_path
    )

    # 5. Define graceful shutdown handler on window close
    def on_close() -> None:
        logger.info("Neurosattva shutdown signal received. Terminating all active playback engines...")
        try:
            runtime_engine.stop_session()
        except Exception as e:
            logger.warning(f"Error stopping session during shutdown: {str(e)}")
            
        try:
            if pygame.mixer.get_init():
                pygame.mixer.quit()
                logger.info("Pygame audio mixer closed.")
        except Exception as e:
            logger.warning(f"Error quitting pygame mixer: {str(e)}")
            
        logger.info("Graceful shutdown complete. Exiting.")
        app.destroy()
        sys.exit(0)

    app.protocol("WM_DELETE_WINDOW", on_close)

    # 6. Start CustomTkinter GUI loop
    logger.info("Launching Neurosattva Window mainloop.")
    app.mainloop()

if __name__ == "__main__":
    main()
