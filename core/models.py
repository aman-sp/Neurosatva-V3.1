from dataclasses import dataclass
from enum import Enum
from typing import List, Dict, Any, Optional

class NeuroState(Enum):
    FOCUS = "focus"
    CREATIVE = "creative"
    CALM = "calm"

    @property
    def audio_filename(self) -> str:
        """Returns the global audio asset filename associated with the state."""
        return f"{self.value}.mp3"

    @property
    def target_brightness(self) -> int:
        """Returns the WLED target brightness (0-255) for this state."""
        if self == NeuroState.FOCUS:
            return 255
        elif self == NeuroState.CREATIVE:
            return 200
        elif self == NeuroState.CALM:
            return 120
        return 0

    @property
    def target_cct(self) -> int:
        """Returns the WLED target Color Temperature (CCT) (0-255) for this state."""
        if self == NeuroState.FOCUS:
            return 220
        elif self == NeuroState.CREATIVE:
            return 128
        elif self == NeuroState.CALM:
            return 30
        return 0

@dataclass(frozen=True)
class TimelineInterval:
    start: float  # Start time in seconds (inclusive)
    end: float    # End time in seconds (exclusive)
    state: NeuroState

    def contains(self, seconds: float) -> bool:
        """Checks if a given timestamp falls within this interval."""
        return self.start <= seconds < self.end

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "TimelineInterval":
        """Creates a TimelineInterval from a parsed JSON dictionary."""
        state_str = str(data.get("state", "")).strip().lower()
        try:
            state = NeuroState(state_str)
        except ValueError:
            raise ValueError(f"Invalid neuro state '{state_str}'. Must be 'focus', 'creative', or 'calm'.")

        try:
            start = float(data["start"])
            end = float(data["end"])
        except (KeyError, ValueError, TypeError):
            raise ValueError("Timeline interval must contain numerical 'start' and 'end' values.")

        if start < 0 or end < 0:
            raise ValueError("Timeline interval start and end times must be non-negative.")
        if start >= end:
            raise ValueError(f"Timeline interval start time ({start}s) must be strictly less than end time ({end}s).")

        return cls(start=start, end=end, state=state)

@dataclass
class LessonModule:
    name: str
    video_filename: str
    video_path: str
    thumbnail_path: Optional[str]
    config_path: str
    timeline: List[TimelineInterval]
    duration: float  # In seconds

    @property
    def scene_count(self) -> int:
        return len(self.timeline)

class EngineState(Enum):
    IDLE = "idle"
    PLAYING = "playing"
    PAUSED = "paused"
    STOPPED = "stopped"
