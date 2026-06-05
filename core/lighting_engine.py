import logging
import threading
from typing import Callable, Optional
import requests

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("LightingEngine")

class LightingEngine:
    def __init__(self, wled_ip: str, transition_ds: int = 50) -> None:
        """
        Initializes the lighting engine.
        wled_ip: Hostname or IP address of the WLED device.
        transition_ds: Transition time in deciseconds (100ms units). Default 50 (5 seconds).
        """
        self.wled_ip: str = wled_ip
        self.transition_ds: int = transition_ds
        self._status_callback: Optional[Callable[[str, bool], None]] = None
        self.is_connected: bool = False
        self._lock = threading.Lock()

    def set_status_callback(self, callback: Callable[[str, bool], None]) -> None:
        """Registers a callback to report WLED connection state updates to the UI."""
        self._status_callback = callback

    def update_wled_ip(self, new_ip: str) -> None:
        """Updates the target WLED IP address."""
        with self._lock:
            self.wled_ip = new_ip

    def _trigger_status_callback(self, message: str, connected: bool) -> None:
        """Helper to invoke status callback safely."""
        self.is_connected = connected
        if self._status_callback:
            try:
                self._status_callback(message, connected)
            except Exception as e:
                logger.error(f"Error executing status callback: {str(e)}")

    def send_state(self, brightness: int, cct: int) -> None:
        """
        Dispatches a CCT and Brightness update to WLED in a background daemon thread
        to maintain total UI responsiveness.
        """
        # Read the IP inside a lock to be thread-safe
        with self._lock:
            ip = self.wled_ip
            transition = self.transition_ds

        thread = threading.Thread(
            target=self._send_state_async,
            args=(ip, brightness, cct, transition),
            daemon=True,
            name="WledNetworkWorker"
        )
        thread.start()

    def _send_state_async(self, ip: str, brightness: int, cct: int, transition: int) -> None:
        """Performs the synchronous WLED POST request on a background thread."""
        if not ip:
            self._trigger_status_callback("IP not configured", False)
            return

        url = f"http://{ip}/json/state"
        payload = {
            "bri": brightness,
            "transition": transition,
            "seg": [
                {
                    "id": 0,
                    "cct": cct
                }
            ]
        }

        try:
            logger.info(f"Sending WLED state update: Brightness={brightness}, CCT={cct} to http://{ip}")
            response = requests.post(url, json=payload, timeout=2.5)
            
            if response.status_code == 200:
                self._trigger_status_callback("Connected", True)
                logger.info("WLED state successfully applied.")
            else:
                self._trigger_status_callback(f"HTTP Error {response.status_code}", False)
                logger.warning(f"WLED API returned non-200 code: {response.status_code}")
                
        except requests.exceptions.Timeout:
            self._trigger_status_callback("Timeout connecting to WLED", False)
            logger.warning("WLED API request timed out.")
        except requests.exceptions.ConnectionError:
            self._trigger_status_callback("WLED unreachable on network", False)
            logger.warning("WLED API request connection error.")
        except Exception as e:
            self._trigger_status_callback(f"Error: {type(e).__name__}", False)
            logger.error(f"Unexpected WLED exception: {str(e)}")
