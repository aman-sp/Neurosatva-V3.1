from typing import List, Optional, Tuple
from core.models import TimelineInterval, NeuroState

class TimelineEngine:
    def __init__(self, timeline: List[TimelineInterval]) -> None:
        """
        Initializes the timeline engine with a list of sorted, validated TimelineIntervals.
        """
        # Ensure intervals are sorted by start time
        self.timeline: List[TimelineInterval] = sorted(timeline, key=lambda x: x.start)

    def get_scene_index_and_interval(self, seconds: float) -> Tuple[int, Optional[TimelineInterval]]:
        """
        Resolves the 0-indexed scene index and corresponding TimelineInterval for the given time.
        Returns (-1, None) if the timestamp falls outside the timeline boundaries.
        """
        for idx, interval in enumerate(self.timeline):
            if interval.contains(seconds):
                return idx, interval
                
        # Edge case: if we are exactly at the end time of the last scene, return the last scene
        if self.timeline and abs(seconds - self.timeline[-1].end) < 0.05:
            return len(self.timeline) - 1, self.timeline[-1]
            
        return -1, None

    def get_state_for_time(self, seconds: float) -> Optional[NeuroState]:
        """Returns the NeuroState for a given video time in seconds."""
        _, interval = self.get_scene_index_and_interval(seconds)
        return interval.state if interval else None

    def get_next_interval(self, seconds: float) -> Optional[TimelineInterval]:
        """Retrieves the upcoming TimelineInterval following the current active interval."""
        curr_idx, _ = self.get_scene_index_and_interval(seconds)
        if curr_idx != -1 and curr_idx + 1 < len(self.timeline):
            return self.timeline[curr_idx + 1]
        return None

    def get_total_duration(self) -> float:
        """Returns the authoritative total timeline duration (end of last interval)."""
        if self.timeline:
            return self.timeline[-1].end
        return 0.0
