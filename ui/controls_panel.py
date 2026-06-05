import customtkinter as ctk
from typing import Callable, Optional
from core.models import EngineState


class ControlsPanel(ctk.CTkFrame):
    def __init__(
        self,
        master: ctk.CTkBaseClass,
        on_start_click: Callable[[], None],
        on_pause_click: Callable[[], None],
        on_stop_click: Callable[[], None],
        **kwargs,
    ) -> None:
        """
        Compact session control strip — Start / Pause-Resume / Stop only.
        """
        super().__init__(
            master,
            corner_radius=12,
            fg_color="#1A1C1F",
            border_width=1,
            border_color="#2A2D32",
            **kwargs,
        )

        self.on_start_click = on_start_click
        self.on_pause_click = on_pause_click
        self.on_stop_click = on_stop_click

        # Three equal columns for the three buttons
        self.grid_columnconfigure((0, 1, 2), weight=1, uniform="ctrl")

        self._setup_ui()
        self.update_state(EngineState.STOPPED, module_loaded=False)

    # ------------------------------------------------------------------ #
    def _setup_ui(self) -> None:
        # Section label
        self.title_lbl = ctk.CTkLabel(
            self,
            text="SESSION CONTROLS",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            text_color="#4F525A",
        )
        self.title_lbl.grid(
            row=0, column=0, columnspan=3, padx=18, pady=(14, 8), sticky="w"
        )

        # ── Start ──────────────────────────────────────────────────────
        self.start_btn = ctk.CTkButton(
            self,
            text="▶  Start Session",
            font=ctk.CTkFont(family="Inter", size=13, weight="bold"),
            fg_color="#1D6A36",
            hover_color="#27924A",
            text_color="#ECEFF4",
            corner_radius=8,
            height=44,
            command=self.on_start_click,
        )
        self.start_btn.grid(row=1, column=0, padx=(14, 5), pady=(0, 14), sticky="ew")

        # ── Pause / Resume ─────────────────────────────────────────────
        self.pause_btn = ctk.CTkButton(
            self,
            text="⏸  Pause",
            font=ctk.CTkFont(family="Inter", size=13, weight="bold"),
            fg_color="#8B5200",
            hover_color="#B36800",
            text_color="#ECEFF4",
            corner_radius=8,
            height=44,
            command=self.on_pause_click,
        )
        self.pause_btn.grid(row=1, column=1, padx=5, pady=(0, 14), sticky="ew")

        # ── Stop ───────────────────────────────────────────────────────
        self.stop_btn = ctk.CTkButton(
            self,
            text="⏹  Stop",
            font=ctk.CTkFont(family="Inter", size=13, weight="bold"),
            fg_color="#7A1A1A",
            hover_color="#A62222",
            text_color="#ECEFF4",
            corner_radius=8,
            height=44,
            command=self.on_stop_click,
        )
        self.stop_btn.grid(row=1, column=2, padx=(5, 14), pady=(0, 14), sticky="ew")

    # ------------------------------------------------------------------ #
    def update_state(self, state: EngineState, module_loaded: bool) -> None:
        """Enable / disable buttons based on engine state."""
        if state in (EngineState.IDLE, EngineState.STOPPED):
            self.start_btn.configure(
                state="normal" if module_loaded else "disabled",
                fg_color="#1D6A36" if module_loaded else "#1A2820",
            )
            self.pause_btn.configure(state="disabled", text="⏸  Pause", fg_color="#2C2218")
            self.stop_btn.configure(state="disabled", fg_color="#2C1A1A")

        elif state == EngineState.PLAYING:
            self.start_btn.configure(state="disabled", fg_color="#1A2820")
            self.pause_btn.configure(
                state="normal", text="⏸  Pause", fg_color="#8B5200"
            )
            self.stop_btn.configure(state="normal", fg_color="#7A1A1A")

        elif state == EngineState.PAUSED:
            self.start_btn.configure(state="disabled", fg_color="#1A2820")
            self.pause_btn.configure(
                state="normal", text="▶  Resume", fg_color="#1D6A36"
            )
            self.stop_btn.configure(state="normal", fg_color="#7A1A1A")
