import os
import json
import logging
from typing import Optional, Dict, Any
import customtkinter as ctk
from tkinter import messagebox
from PIL import Image, ImageDraw

from core.models import EngineState, LessonModule
from core.video_engine import VideoEngine
from core.audio_engine import AudioEngine
from core.lighting_engine import LightingEngine
from core.runtime_engine import RuntimeEngine
from ui.module_panel import ModulePanel
from ui.status_panel import StatusPanel
from ui.controls_panel import ControlsPanel
from ui.vault_panel import VaultPanel
from ui.device_manager_panel import DeviceManagerPanel

logger = logging.getLogger("MainWindow")


class MainWindow(ctk.CTk):
    def __init__(
        self,
        video_engine: VideoEngine,
        audio_engine: AudioEngine,
        lighting_engine: LightingEngine,
        runtime_engine: RuntimeEngine,
        settings_path: str,
    ) -> None:
        super().__init__()

        self.video_engine = video_engine
        self.audio_engine = audio_engine
        self.lighting_engine = lighting_engine
        self.runtime_engine = runtime_engine
        self.settings_path = settings_path

        self.settings: Dict[str, Any] = self._load_settings()

        # ── Window chrome ──────────────────────────────────────────────
        self.title("Neurosattva — Neuro Curriculum Runtime")
        self.geometry("1280x760")
        self.minsize(1100, 680)
        ctk.set_appearance_mode("dark")
        ctk.set_default_color_theme("blue")

        # Root grid: sidebar | workspace
        self.grid_columnconfigure(0, weight=0)
        self.grid_columnconfigure(1, weight=1)
        self.grid_rowconfigure(0, weight=1)

        base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
        self.vault_dir = os.path.join(base_dir, "Digital Valut")

        self._init_icons()
        self._setup_sidebar()
        self._setup_workspace()

        self.runtime_engine.set_ui_callbacks(
            tick_callback=self._on_engine_tick,
            state_change_callback=self._on_engine_state_change,
        )
        self.lighting_engine.set_status_callback(self._on_wled_status_update)
        self._apply_settings_on_startup()
        self._switch_view("dashboard")
        self._fullscreen_win: Optional[ctk.CTkToplevel] = None   # fullscreen overlay
        self._fs_close_win: Optional[ctk.CTkToplevel] = None     # floating exit fullscreen window
        self._periodic_update()

    # ================================================================== #
    # Icon generation helpers
    # ================================================================== #
    def _init_icons(self) -> None:
        """Initialize dynamic high-DPI icons using PIL."""
        fs_pil = self._create_fullscreen_icon(size=64, color="#ECEFF4")
        exit_fs_pil = self._create_exit_fullscreen_icon(size=64, color="#ECEFF4")

        # CustomTkinter CTkImage handles scaling automatically
        self.fs_image = ctk.CTkImage(light_image=fs_pil, dark_image=fs_pil, size=(16, 16))
        self.exit_fs_image = ctk.CTkImage(light_image=exit_fs_pil, dark_image=exit_fs_pil, size=(16, 16))

    def _create_fullscreen_icon(self, size: int = 64, color: str = "#ECEFF4") -> Image.Image:
        """Create a clean vector-style fullscreen icon: 4 corner brackets pointing outwards."""
        img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
        draw = ImageDraw.Draw(img)
        
        # Line thickness
        w = max(2, size // 12)
        # Bracket arm length
        l = size // 4
        # Margin from the edges
        m = size // 8
        
        # Top-Left corner
        draw.line([(m, m), (m + l, m)], fill=color, width=w)
        draw.line([(m, m), (m, m + l)], fill=color, width=w)
        
        # Top-Right corner
        draw.line([(size - 1 - m, m), (size - 1 - m - l, m)], fill=color, width=w)
        draw.line([(size - 1 - m, m), (size - 1 - m, m + l)], fill=color, width=w)
        
        # Bottom-Left corner
        draw.line([(m, size - 1 - m), (m + l, size - 1 - m)], fill=color, width=w)
        draw.line([(m, size - 1 - m), (m, size - 1 - m - l)], fill=color, width=w)
        
        # Bottom-Right corner
        draw.line([(size - 1 - m, size - 1 - m), (size - 1 - m - l, size - 1 - m)], fill=color, width=w)
        draw.line([(size - 1 - m, size - 1 - m), (size - 1 - m, size - 1 - m - l)], fill=color, width=w)
        
        return img

    def _create_exit_fullscreen_icon(self, size: int = 64, color: str = "#ECEFF4") -> Image.Image:
        """Create a clean vector-style exit fullscreen icon: 4 brackets pointing inwards."""
        img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
        draw = ImageDraw.Draw(img)
        
        w = max(2, size // 12)
        l = size // 4
        # Margin from edges (where the arms start)
        m = size // 8
        # Inward offset for the corners of the brackets
        c = size // 3
        
        # Top-Left corner pointing inwards
        draw.line([(c, c), (c - l, c)], fill=color, width=w)
        draw.line([(c, c), (c, c - l)], fill=color, width=w)
        
        # Top-Right corner pointing inwards
        draw.line([(size - 1 - c, c), (size - 1 - c + l, c)], fill=color, width=w)
        draw.line([(size - 1 - c, c), (size - 1 - c, c - l)], fill=color, width=w)
        
        # Bottom-Left corner pointing inwards
        draw.line([(c, size - 1 - c), (c - l, size - 1 - c)], fill=color, width=w)
        draw.line([(c, size - 1 - c), (c, size - 1 - c + l)], fill=color, width=w)
        
        # Bottom-Right corner pointing inwards
        draw.line([(size - 1 - c, size - 1 - c), (size - 1 - c + l, size - 1 - c)], fill=color, width=w)
        draw.line([(size - 1 - c, size - 1 - c), (size - 1 - c, size - 1 - c + l)], fill=color, width=w)
        
        return img

    # ================================================================== #
    # Settings helpers
    # ================================================================== #
    def _load_settings(self) -> Dict[str, Any]:
        defaults = {"wled_ip": "192.168.1.100", "audio_volume": 0.3, "video_volume": 0.8}
        if os.path.isfile(self.settings_path):
            try:
                with open(self.settings_path, "r") as f:
                    return {**defaults, **json.load(f)}
            except Exception as e:
                logger.error(f"Failed to read settings.json: {e}")
        return defaults

    def _save_settings(self, data: Dict[str, Any]) -> None:
        self.settings = data
        try:
            os.makedirs(os.path.dirname(self.settings_path), exist_ok=True)
            with open(self.settings_path, "w") as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to save settings: {e}")

    def _apply_settings_on_startup(self) -> None:
        self.audio_engine.set_volume(self.settings["audio_volume"])
        self.video_engine.set_volume(self.settings["video_volume"])
        self.lighting_engine.update_wled_ip(self.settings["wled_ip"])

    # ================================================================== #
    # Sidebar
    # ================================================================== #
    def _setup_sidebar(self) -> None:
        SB_W = 180
        self.sidebar = ctk.CTkFrame(self, width=SB_W, fg_color="#0D0E10", corner_radius=0)
        self.sidebar.grid(row=0, column=0, sticky="nsew")
        self.sidebar.grid_propagate(False)
        self.sidebar.grid_columnconfigure(0, weight=1)
        self.sidebar.grid_rowconfigure(5, weight=1)   # spacer pushes footer down

        # Brand mark
        brand = ctk.CTkFrame(self.sidebar, fg_color="transparent")
        brand.grid(row=0, column=0, padx=16, pady=(28, 24), sticky="w")

        ctk.CTkLabel(
            brand,
            text="NEUROSATTVA",
            font=ctk.CTkFont(family="Inter", size=16, weight="bold"),
            text_color="#E8EAED",
        ).grid(row=0, column=0, sticky="w")
        ctk.CTkLabel(
            brand,
            text="CURRICULUM ENGINE",
            font=ctk.CTkFont(family="Inter", size=8, weight="bold"),
            text_color="#3A3D42",
        ).grid(row=1, column=0, sticky="w", pady=(2, 0))

        # Divider
        ctk.CTkFrame(self.sidebar, height=1, fg_color="#1E2024").grid(
            row=1, column=0, padx=16, sticky="ew"
        )

        # Nav buttons
        self.nav_buttons: Dict[str, ctk.CTkButton] = {}
        nav_items = [
            ("dashboard", "📊", "Dashboard"),
            ("vault",     "📁", "Digital Vault"),
            ("device",    "🔌", "Device Manager"),
        ]
        for r, (key, icon, label) in enumerate(nav_items, start=2):
            btn = ctk.CTkButton(
                self.sidebar,
                text=f"{icon}  {label}",
                font=ctk.CTkFont(family="Inter", size=12, weight="bold"),
                anchor="w",
                height=42,
                corner_radius=8,
                fg_color="transparent",
                text_color="#5A5E68",
                hover_color="#17191C",
                command=lambda k=key: self._switch_view(k),
            )
            btn.grid(row=r, column=0, padx=10, pady=3, sticky="ew")
            self.nav_buttons[key] = btn

        # Footer — VLC / simulation badge
        footer = ctk.CTkFrame(self.sidebar, fg_color="transparent")
        footer.grid(row=6, column=0, padx=16, pady=18, sticky="sew")
        badge_text = "⚙  SIM MODE" if self.video_engine.use_fallback else "✓  VLC ACTIVE"
        badge_color = "#8B5200" if self.video_engine.use_fallback else "#1D6A36"
        ctk.CTkLabel(
            footer,
            text=badge_text,
            font=ctk.CTkFont(family="Inter", size=9, weight="bold"),
            text_color=badge_color,
        ).grid(row=0, column=0, sticky="w")

    # ================================================================== #
    # Workspace container
    # ================================================================== #
    def _setup_workspace(self) -> None:
        self.workspace = ctk.CTkFrame(self, fg_color="#111315", corner_radius=0)
        self.workspace.grid(row=0, column=1, sticky="nsew")
        self.workspace.grid_columnconfigure(0, weight=1)
        self.workspace.grid_rowconfigure(0, weight=1)

        self._build_dashboard()
        self._build_vault_view()
        self._build_device_view()

    # ── Dashboard ──────────────────────────────────────────────────────
    def _build_dashboard(self) -> None:
        self.dashboard_view = ctk.CTkFrame(self.workspace, fg_color="transparent")
        # 58 % video  |  42 % controls
        self.dashboard_view.grid_columnconfigure(0, weight=58, uniform="d")
        self.dashboard_view.grid_columnconfigure(1, weight=42, uniform="d")
        self.dashboard_view.grid_rowconfigure(0, weight=1)

        # ── LEFT: video + scrubber ──────────────────────────────────
        left = ctk.CTkFrame(self.dashboard_view, fg_color="transparent")
        left.grid(row=0, column=0, padx=(18, 9), pady=18, sticky="nsew")
        left.grid_columnconfigure(0, weight=1)
        left.grid_rowconfigure(0, weight=1)

        # Video card
        video_card = ctk.CTkFrame(
            left, fg_color="#08090A",
            border_width=1, border_color="#22252A", corner_radius=12,
        )
        video_card.grid(row=0, column=0, sticky="nsew")
        video_card.grid_columnconfigure(0, weight=1)
        video_card.grid_rowconfigure(0, weight=1)

        self.video_viewport = ctk.CTkFrame(video_card, fg_color="#000000", corner_radius=10)
        self.video_viewport.grid(row=0, column=0, padx=10, pady=10, sticky="nsew")
        self.video_viewport.grid_columnconfigure(0, weight=1)
        self.video_viewport.grid_rowconfigure(0, weight=1)

        self.viewport_placeholder = ctk.CTkLabel(
            self.video_viewport,
            text="Go to  Digital Vault  and press ▶ Play Sync to begin.",
            font=ctk.CTkFont(family="Inter", size=13),
            text_color="#2E3035",
            justify="center",
            wraplength=380,
        )
        self.viewport_placeholder.grid(row=0, column=0, padx=30, pady=30)

        # Keyboard + double-click bindings on the video viewport
        self.video_viewport.bind("<Double-Button-1>", lambda e: self._toggle_fullscreen())
        self.bind("<F11>", lambda e: self._toggle_fullscreen())
        self.bind("<Escape>", lambda e: self._exit_fullscreen())

        # Scrubber deck
        scrub_card = ctk.CTkFrame(
            left, fg_color="#16181B",
            border_width=1, border_color="#22252A", corner_radius=10, height=58,
        )
        scrub_card.grid(row=1, column=0, pady=(10, 0), sticky="ew")
        scrub_card.grid_propagate(False)
        scrub_card.grid_columnconfigure(1, weight=1)
        scrub_card.grid_columnconfigure(2, weight=0)
        scrub_card.grid_rowconfigure(0, weight=1)

        self.time_lbl = ctk.CTkLabel(
            scrub_card,
            text="00:00 / 00:00",
            font=ctk.CTkFont(family="Courier New", size=12, weight="bold"),
            text_color="#4A4E58",
            width=110,
        )
        self.time_lbl.grid(row=0, column=0, padx=(14, 6))

        self.scrub_slider = ctk.CTkSlider(
            scrub_card,
            from_=0, to=100, number_of_steps=1000,
            progress_color="#00D4D4",
            button_color="#FFFFFF",
            button_hover_color="#00D4D4",
            fg_color="#1E2126",
            height=14,
            command=self._on_timeline_scrub,
        )
        self.scrub_slider.set(0)
        self.scrub_slider.grid(row=0, column=1, padx=(0, 10), sticky="ew")
        self.scrub_slider.configure(state="disabled")

        # ── Fullscreen button (integrated into scrubber deck) ──
        self.fs_btn = ctk.CTkButton(
            scrub_card,
            text="",
            image=self.fs_image,
            width=28, height=28,
            corner_radius=6,
            fg_color="transparent",
            hover_color="#1F2228",
            command=self._toggle_fullscreen,
        )
        self.fs_btn.grid(row=0, column=2, padx=(0, 14), sticky="e")

        # ── RIGHT: module info + telemetry + controls ───────────────
        right = ctk.CTkFrame(self.dashboard_view, fg_color="transparent")
        right.grid(row=0, column=1, padx=(9, 18), pady=18, sticky="nsew")
        right.grid_columnconfigure(0, weight=1)
        right.grid_rowconfigure(0, weight=0)
        right.grid_rowconfigure(1, weight=1)
        right.grid_rowconfigure(2, weight=0)

        self.module_panel = ModulePanel(right)
        self.module_panel.grid(row=0, column=0, sticky="ew", pady=(0, 10))

        self.status_panel = StatusPanel(right)
        self.status_panel.grid(row=1, column=0, sticky="nsew", pady=(0, 10))

        self.controls_panel = ControlsPanel(
            right,
            on_start_click=self._on_start_session,
            on_pause_click=self._on_pause_session,
            on_stop_click=self._on_stop_session,
        )
        self.controls_panel.grid(row=2, column=0, sticky="ew")

    # ── Digital Vault ──────────────────────────────────────────────────
    def _build_vault_view(self) -> None:
        self.vault_view = VaultPanel(
            self.workspace,
            vault_dir=self.vault_dir,
            on_play_module=self._on_play_vault_module,
        )

    # ── Device Manager ─────────────────────────────────────────────────
    def _build_device_view(self) -> None:
        self.device_manager_view = DeviceManagerPanel(
            self.workspace,
            current_settings=self.settings,
            on_save_settings=self._on_save_device_settings,
            on_test_connection=self._on_test_wled_ip,
            on_send_debug_light=self._on_trigger_light_calibration,
        )

    # ================================================================== #
    # Fullscreen
    # ================================================================== #
    def _toggle_fullscreen(self) -> None:
        if self._fullscreen_win is None:
            self._enter_fullscreen()
        else:
            self._exit_fullscreen()

    def _enter_fullscreen(self) -> None:
        """Open a black fullscreen Toplevel and re-embed VLC into it."""
        if self._fullscreen_win is not None:
            return  # already fullscreen

        win = ctk.CTkToplevel(self)
        win.title("Neurosattva — Fullscreen")
        win.attributes("-fullscreen", True)
        win.configure(fg_color="black")
        win.resizable(True, True)

        # Escape / F11 / double-click to exit
        win.bind("<Escape>",          lambda e: self._exit_fullscreen())
        win.bind("<F11>",             lambda e: self._exit_fullscreen())
        win.bind("<Double-Button-1>", lambda e: self._exit_fullscreen())
        win.protocol("WM_DELETE_WINDOW", self._exit_fullscreen)

        # Full-window black frame for VLC rendering
        fs_frame = ctk.CTkFrame(win, fg_color="black", corner_radius=0)
        fs_frame.pack(fill="both", expand=True)

        # Small exit hint label
        hint = ctk.CTkLabel(
            fs_frame,
            text="Press  Esc  or  F11  to exit fullscreen",
            font=ctk.CTkFont(family="Inter", size=11),
            text_color="#3A3D44",
        )
        hint.place(relx=0.5, rely=0.97, anchor="s")

        # Force geometry resolution before embedding VLC
        win.update_idletasks()
        win.update()

        self._fullscreen_win = win
        self._fullscreen_frame = fs_frame

        # Create floating exit button window on top of fullscreen
        try:
            close_win = ctk.CTkToplevel(win)
            close_win.overrideredirect(True)
            close_win.attributes("-topmost", True)
            close_win.configure(fg_color="#1A1C20")

            screen_w = win.winfo_screenwidth()
            close_w = 42
            close_h = 42
            x = screen_w - close_w - 16
            y = 16
            close_win.geometry(f"{close_w}x{close_h}+{x}+{y}")

            exit_btn = ctk.CTkButton(
                close_win,
                text="",
                image=self.exit_fs_image,
                width=close_w, height=close_h,
                corner_radius=8,
                fg_color="#1A1C20",
                hover_color="#00D4D4",
                command=self._exit_fullscreen,
            )
            exit_btn.pack(fill="both", expand=True)
            self._fs_close_win = close_win
        except Exception as e:
            logger.error(f"Failed to create floating fullscreen close button: {e}")
            self._fs_close_win = None

        # Re-embed VLC into the fullscreen frame
        if not self.video_engine.use_fallback and self.video_engine.media_player is not None:
            self.video_engine.media_player.set_hwnd(fs_frame.winfo_id())
            logger.info(f"VLC re-embedded in fullscreen HWND: {fs_frame.winfo_id()}")
        else:
            # Simulation mode — just show a label
            ctk.CTkLabel(
                fs_frame,
                text="SIMULATION ACTIVE\n\nAudio + Lighting running in sync.",
                font=ctk.CTkFont(family="Inter", size=18),
                text_color="#00D4D4",
                justify="center",
            ).place(relx=0.5, rely=0.5, anchor="center")

    def _exit_fullscreen(self) -> None:
        """Close the fullscreen window and restore VLC back to the dashboard viewport."""
        if self._fullscreen_win is None:
            return
        # Restore VLC to original viewport first
        if not self.video_engine.use_fallback and self.video_engine.media_player is not None:
            self.video_engine.media_player.set_hwnd(self.video_viewport.winfo_id())
            logger.info("VLC restored to dashboard viewport.")
        
        # Destroy floating close button
        if self._fs_close_win is not None:
            try:
                self._fs_close_win.destroy()
            except Exception:
                pass
            self._fs_close_win = None

        try:
            self._fullscreen_win.destroy()
        except Exception:
            pass
        self._fullscreen_win = None

    # ================================================================== #
    # View switcher
    # ================================================================== #
    def _switch_view(self, target: str) -> None:
        for btn in self.nav_buttons.values():
            btn.configure(fg_color="transparent", text_color="#5A5E68")

        self.dashboard_view.grid_forget()
        self.vault_view.grid_forget()
        self.device_manager_view.grid_forget()

        if target == "dashboard":
            self.nav_buttons["dashboard"].configure(fg_color="#161A22", text_color="#00D4D4")
            self.dashboard_view.grid(row=0, column=0, sticky="nsew")
        elif target == "vault":
            self.nav_buttons["vault"].configure(fg_color="#161A22", text_color="#00D4D4")
            self.vault_view.refresh_vault()
            self.vault_view.grid(row=0, column=0, sticky="nsew")
        elif target == "device":
            self.nav_buttons["device"].configure(fg_color="#161A22", text_color="#00D4D4")
            self.device_manager_view.grid(row=0, column=0, sticky="nsew")

    # ================================================================== #
    # Engine interaction
    # ================================================================== #
    def _on_play_vault_module(self, module_dir_path: str) -> None:
        try:
            logger.info(f"Vault play → {module_dir_path}")
            module = self.runtime_engine.load_module(module_dir_path)
            self.module_panel.display_module(module)
            self.status_panel.reset()
            self.scrub_slider.configure(state="disabled")
            self.scrub_slider.set(0)
            self.time_lbl.configure(text="00:00 / 00:00", text_color="#4A4E58")
            self.viewport_placeholder.configure(text="")
            self.controls_panel.update_state(EngineState.STOPPED, module_loaded=True)
            self._switch_view("dashboard")
            self._on_start_session()
        except Exception as e:
            logger.error(f"Vault autoplay failed: {e}")
            messagebox.showerror("Vault Playback Error", f"Could not launch module:\n\n{e}")

    def _on_start_session(self) -> None:
        if not self.runtime_engine.active_module:
            return
        self.viewport_placeholder.configure(text="")
        hwnd_id = self.video_viewport.winfo_id() if not self.video_engine.use_fallback else None
        try:
            self.runtime_engine.start_session(tk_frame_id=hwnd_id)
            mod = self.runtime_engine.active_module
            self.scrub_slider.configure(state="normal", from_=0, to=mod.duration)
            if self.video_engine.use_fallback:
                self.viewport_placeholder.configure(
                    text="SIMULATION RUNNING\n\nAudio crossfades active · WLED triggering",
                    text_color="#00D4D4",
                )
        except Exception as e:
            logger.error(f"Start session failed: {e}")
            messagebox.showerror("Playback Error", f"Could not start session:\n\n{e}")

    def _on_pause_session(self) -> None:
        if self.runtime_engine.state == EngineState.PLAYING:
            self.runtime_engine.pause_session()
            if self.video_engine.use_fallback:
                self.viewport_placeholder.configure(
                    text="SIMULATION PAUSED", text_color="#E08C00"
                )
        elif self.runtime_engine.state == EngineState.PAUSED:
            self.runtime_engine.resume_session()
            if self.video_engine.use_fallback:
                self.viewport_placeholder.configure(
                    text="SIMULATION RUNNING\n\nAudio crossfades active · WLED triggering",
                    text_color="#00D4D4",
                )

    def _on_stop_session(self) -> None:
        self.runtime_engine.stop_session()
        self.status_panel.reset()
        self.scrub_slider.set(0)
        self.scrub_slider.configure(state="disabled")
        self.time_lbl.configure(text="00:00 / 00:00", text_color="#4A4E58")
        mod = self.runtime_engine.active_module
        if mod:
            self.viewport_placeholder.configure(
                text=f"{mod.name.upper()} loaded — press Start Session to begin.",
                text_color="#3A3D42",
            )
        else:
            self.viewport_placeholder.configure(
                text="Go to  Digital Vault  and press ▶ Play Sync to begin.",
                text_color="#2E3035",
            )

    def _on_timeline_scrub(self, val: float) -> None:
        self.runtime_engine.seek_session(val)

    # ================================================================== #
    # Device manager callbacks
    # ================================================================== #
    def _on_save_device_settings(self, new_settings: Dict[str, Any]) -> None:
        self._save_settings(new_settings)
        self._apply_settings_on_startup()
        messagebox.showinfo("Settings Saved", "WLED IP and audio levels updated.")

    def _on_test_wled_ip(self, ip: str) -> None:
        self.lighting_engine.update_wled_ip(ip)
        self.lighting_engine.send_state(brightness=50, cct=128)

    def _on_trigger_light_calibration(self, brightness: int, cct: int) -> None:
        self.lighting_engine.send_state(brightness=brightness, cct=cct)

    # ================================================================== #
    # Engine observer callbacks
    # ================================================================== #
    def _on_engine_tick(self, data: Dict[str, Any]) -> None:
        self.status_panel.update_telemetry(data)
        curr = data["current_time"]
        dur  = data["duration"]
        self.scrub_slider.set(curr)

        # Update inline time label
        cm, cs = int(curr // 60), int(curr % 60)
        dm, ds = int(dur  // 60), int(dur  % 60)
        self.time_lbl.configure(
            text=f"{cm:02d}:{cs:02d} / {dm:02d}:{ds:02d}",
            text_color="#8A8E98",
        )

    def _on_engine_state_change(self, state: EngineState) -> None:
        has_module = self.runtime_engine.active_module is not None
        self.controls_panel.update_state(state, module_loaded=has_module)
        if state in (EngineState.STOPPED, EngineState.IDLE):
            self.scrub_slider.set(0)
            self.scrub_slider.configure(state="disabled")
            self.time_lbl.configure(text="00:00 / 00:00", text_color="#4A4E58")

    def _on_wled_status_update(self, msg: str, connected: bool) -> None:
        self.status_panel.update_wled_connection(msg, connected)
        self.device_manager_view.update_wled_connection(msg, connected)

    # ================================================================== #
    # Periodic tick
    # ================================================================== #
    def _periodic_update(self) -> None:
        self.runtime_engine.update_tick()
        self.after(100, self._periodic_update)
