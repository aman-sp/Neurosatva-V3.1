import os
import logging
import customtkinter as ctk
from typing import Optional
from core.models import LessonModule

logger = logging.getLogger("ModulePanel")

PIL_AVAILABLE = False
try:
    from PIL import Image
    PIL_AVAILABLE = True
except ImportError:
    pass

_BG     = "#16181B"
_CARD   = "#1A1C20"
_BORDER = "#22252A"
_DIM    = "#3A3D44"
_MID    = "#5A5E68"
_BRIGHT = "#E8EAED"
_CYAN   = "#00D4D4"
_ORANGE = "#FD8C00"

# Solid dark tint per accent — Tkinter rejects 8-digit hex (#RRGGBBAA)
_TINT = {
    _CYAN:   "#0A1E1E",
    _ORANGE: "#201400",
}


class ModulePanel(ctk.CTkFrame):
    def __init__(self, master: ctk.CTkBaseClass, **kwargs) -> None:
        super().__init__(
            master,
            corner_radius=12,
            fg_color=_BG,
            border_width=1,
            border_color=_BORDER,
            **kwargs,
        )
        self.grid_columnconfigure(0, weight=1)
        self._build_empty()

    # ------------------------------------------------------------------ #
    def _build_empty(self) -> None:
        for w in self.winfo_children():
            w.destroy()

        ctk.CTkLabel(
            self,
            text="CURRICULUM MODULE",
            font=ctk.CTkFont(family="Inter", size=10, weight="bold"),
            text_color=_DIM,
        ).grid(row=0, column=0, padx=14, pady=(12, 6), sticky="w")

        ctk.CTkLabel(
            self,
            text="No module loaded — open Digital Vault to select one.",
            font=ctk.CTkFont(family="Inter", size=11),
            text_color=_MID,
            justify="left",
        ).grid(row=1, column=0, padx=14, pady=(0, 14), sticky="w")

    # ------------------------------------------------------------------ #
    def display_module(self, module: LessonModule) -> None:
        for w in self.winfo_children():
            w.destroy()

        # Section label
        ctk.CTkLabel(
            self,
            text="CURRICULUM MODULE",
            font=ctk.CTkFont(family="Inter", size=10, weight="bold"),
            text_color=_DIM,
        ).grid(row=0, column=0, columnspan=2, padx=14, pady=(12, 6), sticky="w")

        # ── Thumbnail ──────────────────────────────────────────────
        thumb_size = (80, 52)
        img = None
        if PIL_AVAILABLE and module.thumbnail_path and os.path.isfile(module.thumbnail_path):
            try:
                pil = Image.open(module.thumbnail_path).resize(thumb_size, Image.Resampling.LANCZOS)
                img = ctk.CTkImage(light_image=pil, dark_image=pil, size=thumb_size)
            except Exception:
                pass

        thumb_frame = ctk.CTkFrame(
            self, width=thumb_size[0], height=thumb_size[1],
            fg_color=_CARD, border_width=1, border_color=_BORDER, corner_radius=6,
        )
        thumb_frame.grid(row=1, column=0, padx=(14, 10), pady=(0, 14), sticky="nw")
        thumb_frame.grid_propagate(False)
        thumb_frame.grid_columnconfigure(0, weight=1)
        thumb_frame.grid_rowconfigure(0, weight=1)

        if img:
            ctk.CTkLabel(thumb_frame, image=img, text="").grid(row=0, column=0)
        else:
            ctk.CTkLabel(
                thumb_frame, text="📘",
                font=ctk.CTkFont(size=22),
            ).grid(row=0, column=0)

        # ── Module meta ────────────────────────────────────────────
        info = ctk.CTkFrame(self, fg_color="transparent")
        info.grid(row=1, column=1, padx=(0, 14), pady=(0, 14), sticky="nsew")
        info.grid_columnconfigure(0, weight=1)
        self.grid_columnconfigure(1, weight=1)

        ctk.CTkLabel(
            info,
            text=module.name,
            font=ctk.CTkFont(family="Inter", size=15, weight="bold"),
            text_color=_BRIGHT,
            anchor="w",
        ).grid(row=0, column=0, columnspan=2, sticky="w")

        ctk.CTkLabel(
            info,
            text=module.video_filename,
            font=ctk.CTkFont(family="Inter", size=10),
            text_color=_MID,
            anchor="w",
        ).grid(row=1, column=0, columnspan=2, sticky="w", pady=(1, 6))

        # Duration pill
        mins, secs = int(module.duration // 60), int(module.duration % 60)
        self._pill(info, 2, 0, f"{mins:02d}:{secs:02d}", _CYAN)
        self._pill(info, 2, 1, f"{module.scene_count} stages", _ORANGE)

    # ------------------------------------------------------------------ #
    def _pill(self, parent, row, col, text, colour) -> None:
        tint = _TINT.get(colour, "#1A1C20")
        f = ctk.CTkFrame(parent, fg_color=tint, corner_radius=6)
        f.grid(row=row, column=col, padx=(0, 6), sticky="w")
        ctk.CTkLabel(
            f, text=text,
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            text_color=colour,
        ).grid(padx=8, pady=3)

    # ------------------------------------------------------------------ #
    def reset(self) -> None:
        self._build_empty()
