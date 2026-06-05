import customtkinter as ctk
from typing import Dict, Any
from core.models import NeuroState

# ── colour palette ────────────────────────────────────────────────────────────
_BG      = "#16181B"
_CARD    = "#1A1C20"
_BORDER  = "#22252A"
_DIM     = "#3A3D44"
_MID     = "#5A5E68"
_BRIGHT  = "#E8EAED"
_CYAN    = "#00D4D4"
_ORANGE  = "#E08C00"
_GREEN   = "#2DB56A"
_RED     = "#D94040"


class StatusPanel(ctk.CTkFrame):
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
        self._build_ui()

    # ------------------------------------------------------------------ #
    def _build_ui(self) -> None:
        # ── Section header ────────────────────────────────────────────
        ctk.CTkLabel(
            self,
            text="LIVE TELEMETRY",
            font=ctk.CTkFont(family="Inter", size=10, weight="bold"),
            text_color=_DIM,
        ).grid(row=0, column=0, padx=14, pady=(12, 8), sticky="w")

        # ── Big clock ─────────────────────────────────────────────────
        clock_card = ctk.CTkFrame(self, fg_color=_CARD, corner_radius=8, border_width=1, border_color=_BORDER)
        clock_card.grid(row=1, column=0, padx=12, pady=(0, 8), sticky="ew")
        clock_card.grid_columnconfigure(0, weight=1)

        self.clock_lbl = ctk.CTkLabel(
            clock_card,
            text="00:00",
            font=ctk.CTkFont(family="Courier New", size=38, weight="bold"),
            text_color=_BRIGHT,
        )
        self.clock_lbl.grid(row=0, column=0, padx=14, pady=(10, 2), sticky="w")

        self.duration_lbl = ctk.CTkLabel(
            clock_card,
            text="of  00:00",
            font=ctk.CTkFont(family="Inter", size=11),
            text_color=_MID,
        )
        self.duration_lbl.grid(row=1, column=0, padx=14, pady=(0, 4), sticky="w")

        # Progress bar
        self.progress_bar = ctk.CTkProgressBar(
            clock_card, height=6, corner_radius=3,
            progress_color=_CYAN, fg_color="#202428",
        )
        self.progress_bar.set(0)
        self.progress_bar.grid(row=2, column=0, padx=12, pady=(4, 10), sticky="ew")

        # ── Neuro-state badge ─────────────────────────────────────────
        self.state_card = ctk.CTkFrame(
            self, fg_color=_CARD, corner_radius=8,
            border_width=1, border_color=_BORDER,
        )
        self.state_card.grid(row=2, column=0, padx=12, pady=(0, 8), sticky="ew")
        self.state_card.grid_columnconfigure(1, weight=1)

        self.state_dot = ctk.CTkFrame(
            self.state_card, width=10, height=10,
            corner_radius=5, fg_color=_DIM,
        )
        self.state_dot.grid(row=0, column=0, padx=(12, 8), pady=12)
        self.state_dot.grid_propagate(False)

        self.state_lbl = ctk.CTkLabel(
            self.state_card,
            text="SYSTEM IDLE",
            font=ctk.CTkFont(family="Inter", size=12, weight="bold"),
            text_color=_MID,
            anchor="w",
        )
        self.state_lbl.grid(row=0, column=1, sticky="w", pady=12)

        self.scene_lbl = ctk.CTkLabel(
            self.state_card,
            text="—",
            font=ctk.CTkFont(family="Inter", size=10),
            text_color=_DIM,
            anchor="e",
        )
        self.scene_lbl.grid(row=0, column=2, padx=(4, 14), pady=12, sticky="e")

        # ── Two-column stats row ──────────────────────────────────────
        stats = ctk.CTkFrame(self, fg_color="transparent")
        stats.grid(row=3, column=0, padx=12, pady=(0, 8), sticky="ew")
        stats.grid_columnconfigure((0, 1), weight=1, uniform="stat")

        self._audio_card  = self._stat_card(stats, 0, "NEURO AUDIO",    "—")
        self._light_card  = self._stat_card(stats, 1, "LIGHTING (Bri/CCT)", "—")

        # ── WLED status strip ─────────────────────────────────────────
        wled_strip = ctk.CTkFrame(
            self, fg_color=_CARD, corner_radius=8,
            border_width=1, border_color=_BORDER,
        )
        wled_strip.grid(row=4, column=0, padx=12, pady=(0, 12), sticky="ew")
        wled_strip.grid_columnconfigure(1, weight=1)

        self.wled_dot = ctk.CTkFrame(
            wled_strip, width=8, height=8, corner_radius=4, fg_color=_RED,
        )
        self.wled_dot.grid(row=0, column=0, padx=(12, 6), pady=10)
        self.wled_dot.grid_propagate(False)

        self.wled_lbl = ctk.CTkLabel(
            wled_strip,
            text="WLED  ·  DISCONNECTED",
            font=ctk.CTkFont(family="Inter", size=10, weight="bold"),
            text_color=_MID,
            anchor="w",
        )
        self.wled_lbl.grid(row=0, column=1, sticky="w")

        self.wled_ping = ctk.CTkLabel(
            wled_strip,
            text="",
            font=ctk.CTkFont(family="Inter", size=9),
            text_color=_DIM,
            anchor="e",
        )
        self.wled_ping.grid(row=0, column=2, padx=(0, 12), sticky="e")

    # ------------------------------------------------------------------ #
    def _stat_card(self, parent, col: int, title: str, init_val: str):
        """Helper — builds a small labelled stat tile."""
        padx = (0, 6) if col == 0 else (6, 0)
        card = ctk.CTkFrame(parent, fg_color=_CARD, corner_radius=8, border_width=1, border_color=_BORDER)
        card.grid(row=0, column=col, padx=padx, sticky="ew")
        card.grid_columnconfigure(0, weight=1)

        ctk.CTkLabel(
            card, text=title,
            font=ctk.CTkFont(family="Inter", size=9, weight="bold"),
            text_color=_DIM, anchor="w",
        ).grid(row=0, column=0, padx=10, pady=(8, 2), sticky="w")

        val_lbl = ctk.CTkLabel(
            card, text=init_val,
            font=ctk.CTkFont(family="Inter", size=12, weight="bold"),
            text_color=_BRIGHT, anchor="w",
        )
        val_lbl.grid(row=1, column=0, padx=10, pady=(0, 8), sticky="w")
        return val_lbl

    # ================================================================== #
    # Public update API
    # ================================================================== #
    def update_wled_connection(self, msg: str, connected: bool) -> None:
        if connected:
            self.wled_dot.configure(fg_color=_GREEN)
            self.wled_lbl.configure(text="WLED  ·  CONNECTED", text_color=_GREEN)
        else:
            self.wled_dot.configure(fg_color=_RED)
            self.wled_lbl.configure(text="WLED  ·  DISCONNECTED", text_color=_MID)
        self.wled_ping.configure(text=msg.upper())

    def update_telemetry(self, data: Dict[str, Any]) -> None:
        curr     = data["current_time"]
        dur      = data["duration"]
        progress = data["progress"]
        state    = data["active_neuro_state"]
        audio    = data["audio_profile"]
        bri      = data["lighting_brightness"]
        cct      = data["lighting_cct"]
        scene    = data["current_scene_name"]

        # Clock
        cm, cs = int(curr // 60), int(curr % 60)
        dm, ds = int(dur  // 60), int(dur  % 60)
        self.clock_lbl.configure(text=f"{cm:02d}:{cs:02d}")
        self.duration_lbl.configure(text=f"of  {dm:02d}:{ds:02d}   ({int(progress*100)}%)")
        self.progress_bar.set(progress)

        # Stats
        self._audio_card.configure(text=audio if audio != "None" else "—")
        self._light_card.configure(text=f"{bri} bri  /  {cct} cct")

        # Neuro state badge
        if state == NeuroState.FOCUS:
            colour = "#FD8C00"
            label  = "FOCUS MODE"
        elif state == NeuroState.CREATIVE:
            colour = _CYAN
            label  = "CREATIVE MODE"
        elif state == NeuroState.CALM:
            colour = _GREEN
            label  = "CALM MODE"
        else:
            colour = _DIM
            label  = "SYSTEM IDLE"

        self.state_dot.configure(fg_color=colour)
        self.state_lbl.configure(text=label, text_color=colour)
        self.scene_lbl.configure(text=scene.split(":")[-1].strip().upper())
        self.state_card.configure(border_color=colour)
        self.progress_bar.configure(progress_color=colour)

    def reset(self) -> None:
        self.clock_lbl.configure(text="00:00")
        self.duration_lbl.configure(text="of  00:00   (0%)")
        self.progress_bar.set(0)
        self.progress_bar.configure(progress_color=_CYAN)
        self.state_dot.configure(fg_color=_DIM)
        self.state_lbl.configure(text="SYSTEM IDLE", text_color=_MID)
        self.state_card.configure(border_color=_BORDER)
        self.scene_lbl.configure(text="—")
        self._audio_card.configure(text="—")
        self._light_card.configure(text="—")

    # kept for backward compatibility
    def reset_state_indicator(self) -> None:
        self.reset()
