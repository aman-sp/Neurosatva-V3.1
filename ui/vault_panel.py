import os
import logging
import customtkinter as ctk
from typing import Callable, List, Tuple, Optional
from core.module_loader import ModuleLoader
from core.models import LessonModule

logger = logging.getLogger("VaultPanel")

_BG     = "#111315"
_CARD   = "#16181B"
_CARD2  = "#1A1C20"
_BORDER = "#22252A"
_DIM    = "#3A3D44"
_MID    = "#5A5E68"
_BRIGHT = "#E8EAED"
_CYAN   = "#00D4D4"
_GREEN  = "#2DB56A"
_RED    = "#D94040"
_ORANGE = "#FD8C00"

# Solid dark-tint backgrounds (no alpha — Tkinter doesn't support 8-digit hex)
_TINT = {
    _CYAN:   "#0A1E1E",
    _ORANGE: "#201400",
    _MID:    "#1A1C20",
    _RED:    "#2A0E0E",
    _GREEN:  "#0A1E10",
}


class VaultPanel(ctk.CTkFrame):
    def __init__(
        self,
        master: ctk.CTkBaseClass,
        vault_dir: str,
        on_play_module: Callable[[str], None],
        **kwargs,
    ) -> None:
        super().__init__(master, corner_radius=0, fg_color=_BG, **kwargs)
        self.vault_dir = vault_dir
        self.on_play_module = on_play_module

        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(1, weight=1)

        self._build_header()
        self._build_scroll()
        self.refresh_vault()

    # ------------------------------------------------------------------ #
    def _build_header(self) -> None:
        hdr = ctk.CTkFrame(self, fg_color="transparent")
        hdr.grid(row=0, column=0, padx=22, pady=(22, 10), sticky="ew")
        hdr.grid_columnconfigure(0, weight=1)

        ctk.CTkLabel(
            hdr,
            text="DIGITAL VAULT",
            font=ctk.CTkFont(family="Inter", size=20, weight="bold"),
            text_color=_BRIGHT,
            anchor="w",
        ).grid(row=0, column=0, sticky="w")

        ctk.CTkLabel(
            hdr,
            text=f"Auto-scanning  /  {os.path.basename(self.vault_dir)}",
            font=ctk.CTkFont(family="Inter", size=10),
            text_color=_DIM,
            anchor="w",
        ).grid(row=1, column=0, sticky="w", pady=(2, 0))

        ctk.CTkButton(
            hdr,
            text="⟳  Refresh",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            fg_color=_CARD2,
            hover_color="#20232A",
            text_color=_MID,
            corner_radius=8,
            width=100, height=32,
            command=self.refresh_vault,
        ).grid(row=0, column=1, rowspan=2, padx=(12, 0), sticky="e")

    def _build_scroll(self) -> None:
        self.scroll = ctk.CTkScrollableFrame(
            self, fg_color=_CARD,
            corner_radius=12, border_width=1, border_color=_BORDER,
        )
        self.scroll.grid(row=1, column=0, padx=22, pady=(0, 22), sticky="nsew")
        self.scroll.grid_columnconfigure(0, weight=1)

    # ------------------------------------------------------------------ #
    def refresh_vault(self) -> None:
        for w in self.scroll.winfo_children():
            w.destroy()

        logger.info(f"Scanning vault: {self.vault_dir}")
        items = ModuleLoader.discover_vault(self.vault_dir)

        if not items:
            ctk.CTkLabel(
                self.scroll,
                text="Vault is empty.\n\nAdd a module folder containing config.json + video to  Digital Valut/",
                font=ctk.CTkFont(family="Inter", size=12),
                text_color=_DIM,
                justify="center",
            ).grid(row=0, column=0, pady=80)
            return

        for idx, (folder, module, err) in enumerate(items):
            self._build_card(idx, folder, module, err)

    # ------------------------------------------------------------------ #
    def _build_card(
        self,
        row_idx: int,
        folder_name: str,
        module: Optional[LessonModule],
        error_msg: Optional[str],
    ) -> None:
        card = ctk.CTkFrame(
            self.scroll, fg_color=_CARD2,
            corner_radius=10, border_width=1, border_color=_BORDER,
        )
        card.grid(row=row_idx, column=0, padx=10, pady=6, sticky="ew")
        card.grid_columnconfigure(1, weight=1)

        if error_msg:
            # Error icon
            ctk.CTkLabel(card, text="✗", font=ctk.CTkFont(size=20), text_color=_RED).grid(
                row=0, column=0, rowspan=2, padx=(16, 12), pady=16
            )
            ctk.CTkLabel(
                card, text=folder_name,
                font=ctk.CTkFont(family="Inter", size=13, weight="bold"),
                text_color=_BRIGHT, anchor="w",
            ).grid(row=0, column=1, sticky="w", pady=(14, 2))

            short = error_msg if len(error_msg) < 90 else error_msg[:87] + "…"
            ctk.CTkLabel(
                card, text=f"Config error: {short}",
                font=ctk.CTkFont(family="Inter", size=10),
                text_color=_RED, anchor="w",
            ).grid(row=1, column=1, sticky="w", pady=(0, 14))

            ctk.CTkButton(
                card, text="Invalid",
                font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
                fg_color="#2A1010", text_color="#5A2020",
                state="disabled", corner_radius=8, width=110, height=34,
            ).grid(row=0, column=2, rowspan=2, padx=16, pady=16)

        else:
            assert module is not None
            mins, secs = int(module.duration // 60), int(module.duration % 60)

            # Book icon bead
            icon_bead = ctk.CTkFrame(
                card, width=46, height=46,
                fg_color="#111315", corner_radius=8, border_width=1, border_color=_BORDER,
            )
            icon_bead.grid(row=0, column=0, rowspan=2, padx=(14, 12), pady=14)
            icon_bead.grid_propagate(False)
            icon_bead.grid_columnconfigure(0, weight=1)
            icon_bead.grid_rowconfigure(0, weight=1)
            ctk.CTkLabel(icon_bead, text="📘", font=ctk.CTkFont(size=20)).grid(row=0, column=0)

            # Module name
            ctk.CTkLabel(
                card, text=module.name,
                font=ctk.CTkFont(family="Inter", size=14, weight="bold"),
                text_color=_BRIGHT, anchor="w",
            ).grid(row=0, column=1, sticky="w", pady=(14, 2))

            # Meta row
            meta = ctk.CTkFrame(card, fg_color="transparent")
            meta.grid(row=1, column=1, sticky="w", pady=(0, 14))

            for i, (txt, col) in enumerate([
                (f"{mins:02d}:{secs:02d}", _CYAN),
                (f"{module.scene_count} stages", _ORANGE),
                (module.video_filename, _MID),
            ]):
                tint = _TINT.get(col, _CARD2)
                f = ctk.CTkFrame(meta, fg_color=tint, corner_radius=5)
                f.grid(row=0, column=i, padx=(0, 6))
                ctk.CTkLabel(
                    f, text=txt,
                    font=ctk.CTkFont(family="Inter", size=10, weight="bold"),
                    text_color=col,
                ).grid(padx=7, pady=3)

            # Play button
            module_path = os.path.dirname(module.config_path)
            ctk.CTkButton(
                card,
                text="▶  Play Sync",
                font=ctk.CTkFont(family="Inter", size=12, weight="bold"),
                fg_color="#1D6A36",
                hover_color="#27924A",
                text_color="#ECEFF4",
                corner_radius=8,
                width=120, height=36,
                command=lambda p=module_path: self.on_play_module(p),
            ).grid(row=0, column=2, rowspan=2, padx=16, pady=14, sticky="e")
