import os
import json
import logging
from typing import List, Dict, Any, Tuple, Optional
from core.models import LessonModule, TimelineInterval, NeuroState

logger = logging.getLogger("ModuleLoader")

class ModuleLoaderError(Exception):
    """Custom exception class for curriculum module loading and validation errors."""
    pass

class ModuleLoader:
    @staticmethod
    def load_from_directory(module_dir_path: str) -> LessonModule:
        """
        Loads and validates a curriculum lesson module from its directory path.
        Expectes the directory to contain a config.json and the video specified in config.json.
        An optional thumbnail.png may be present.
        """
        if not os.path.isdir(module_dir_path):
            raise ModuleLoaderError(f"Provided path is not a valid directory: {module_dir_path}")

        config_path = os.path.join(module_dir_path, "config.json")
        if not os.path.isfile(config_path):
            raise ModuleLoaderError(f"Missing required configuration file 'config.json' in {module_dir_path}")

        # Parse config.json
        try:
            with open(config_path, "r", encoding="utf-8") as f:
                config_data = json.load(f)
        except json.JSONDecodeError as e:
            raise ModuleLoaderError(f"Failed to parse 'config.json' due to invalid JSON syntax: {str(e)}")
        except Exception as e:
            raise ModuleLoaderError(f"Error reading 'config.json': {str(e)}")

        # Validate basic fields
        module_name = config_data.get("module_name")
        if not module_name or not isinstance(module_name, str):
            raise ModuleLoaderError("Missing or invalid 'module_name' in config.json. It must be a non-empty string.")

        video_filename = config_data.get("video")
        if not video_filename or not isinstance(video_filename, str):
            raise ModuleLoaderError("Missing or invalid 'video' file name in config.json. It must be a non-empty string.")

        video_path = os.path.join(module_dir_path, video_filename)
        if not os.path.isfile(video_path):
            raise ModuleLoaderError(f"Video file specified in config.json does not exist: {video_path}")

        # Validate thumbnail (optional, checks thumbnail.png as default if not explicitly set)
        thumbnail_filename = config_data.get("thumbnail", "thumbnail.png")
        thumbnail_path = os.path.join(module_dir_path, thumbnail_filename)
        if not os.path.isfile(thumbnail_path):
            thumbnail_path = None  # Telemetry panel will handle missing thumbnail gracefully

        # Validate timeline
        timeline_list = config_data.get("timeline")
        if not timeline_list or not isinstance(timeline_list, list):
            raise ModuleLoaderError("Missing or invalid 'timeline' array in config.json.")

        parsed_intervals: List[TimelineInterval] = []
        for idx, item in enumerate(timeline_list):
            if not isinstance(item, dict):
                raise ModuleLoaderError(f"Timeline item at index {idx} must be a JSON object.")
            try:
                interval = TimelineInterval.from_dict(item)
                parsed_intervals.append(interval)
            except ValueError as e:
                raise ModuleLoaderError(f"Invalid timeline entry at index {idx}: {str(e)}")

        if not parsed_intervals:
            raise ModuleLoaderError("The timeline must contain at least one interval.")

        # Sort intervals by start time
        parsed_intervals.sort(key=lambda x: x.start)

        # Validate contiguous timeline constraints
        # 1. Must start at 0
        if parsed_intervals[0].start != 0.0:
            raise ModuleLoaderError(
                f"Invalid timeline start. The first timeline interval must start at 0 seconds, but starts at {parsed_intervals[0].start}s."
            )

        # 2. Gaps & overlaps verification
        for idx in range(len(parsed_intervals) - 1):
            curr_int = parsed_intervals[idx]
            next_int = parsed_intervals[idx + 1]
            if curr_int.end != next_int.start:
                if curr_int.end < next_int.start:
                    raise ModuleLoaderError(
                        f"Gap detected in timeline: Scene {idx} ends at {curr_int.end}s, but Scene {idx + 1} does not start until {next_int.start}s."
                    )
                else:
                    raise ModuleLoaderError(
                        f"Overlap detected in timeline: Scene {idx} ends at {curr_int.end}s, which conflicts with Scene {idx + 1} starting at {next_int.start}s."
                    )

        # Authoritative duration is the end of the last timeline interval
        total_duration = parsed_intervals[-1].end

        return LessonModule(
            name=module_name,
            video_filename=video_filename,
            video_path=video_path,
            thumbnail_path=thumbnail_path,
            config_path=config_path,
            timeline=parsed_intervals,
            duration=total_duration
        )

    @staticmethod
    def discover_vault(vault_dir_path: str) -> List[Tuple[str, Optional[LessonModule], Optional[str]]]:
        """
        Scans a parent folder directory (the Digital Vault) for subfolders.
        Attempts to parse and validate each subfolder.
        Returns a list of tuples: (subfolder_name, LessonModule or None, error_message or None)
        """
        results = []
        if not os.path.isdir(vault_dir_path):
            return results
            
        try:
            for item in sorted(os.listdir(vault_dir_path)):
                item_path = os.path.join(vault_dir_path, item)
                if os.path.isdir(item_path):
                    try:
                        module = ModuleLoader.load_from_directory(item_path)
                        results.append((item, module, None))
                    except Exception as e:
                        results.append((item, None, str(e)))
        except Exception as e:
            logger.error(f"Error scanning vault directory: {str(e)}")
            
        return results

