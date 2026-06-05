import customtkinter as ctk
import logging
from typing import Dict, Any, Callable

logger = logging.getLogger("DeviceManager")

class DeviceManagerPanel(ctk.CTkFrame):
    def __init__(
        self,
        master: ctk.CTkBaseClass,
        current_settings: Dict[str, Any],
        on_save_settings: Callable[[Dict[str, Any]], None],
        on_test_connection: Callable[[str], None],
        on_send_debug_light: Callable[[int, int], None],
        **kwargs
    ) -> None:
        """
        Initializes the Device Manager hardware workspace.
        """
        super().__init__(master, corner_radius=10, fg_color="#1E2022", border_width=1, border_color="#2D3035", **kwargs)
        
        self.settings = current_settings
        self.on_save_settings = on_save_settings
        self.on_test_connection = on_test_connection
        self.on_send_debug_light = on_send_debug_light
        
        self.grid_columnconfigure(0, weight=1, uniform="dm_cols")
        self.grid_columnconfigure(1, weight=1, uniform="dm_cols")
        self.grid_rowconfigure(1, weight=1) # Main configurations grid stretches

        self._setup_ui()

    def _setup_ui(self) -> None:
        # Title Deck Header
        self.header_lbl = ctk.CTkLabel(
            self,
            text="HARDWARE CONTROL ROOM & DEVICE MANAGER",
            font=ctk.CTkFont(family="Inter", size=16, weight="bold"),
            text_color="#ECEFF4",
            anchor="w"
        )
        self.header_lbl.grid(row=0, column=0, columnspan=2, padx=20, pady=(20, 10), sticky="w")

        # ----------------------------------------------------
        # Left Workspace Column: ESP32 Linker & Volumes Deck
        # ----------------------------------------------------
        self.left_frame = ctk.CTkFrame(self, fg_color="#161719", corner_radius=8, border_width=1, border_color="#25262B")
        self.left_frame.grid(row=1, column=0, padx=(20, 10), pady=(0, 20), sticky="nsew")
        self.left_frame.grid_columnconfigure(0, weight=1)

        # 1. ESP32 Config Section
        self.esp_title = ctk.CTkLabel(
            self.left_frame,
            text="ESP32 WLED LIGHTING LINKER",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            text_color="#4F525A"
        )
        self.esp_title.grid(row=0, column=0, padx=15, pady=(15, 8), sticky="w")

        self.ip_row = ctk.CTkFrame(self.left_frame, fg_color="transparent")
        self.ip_row.grid(row=1, column=0, padx=15, pady=(0, 12), sticky="ew")
        self.ip_row.grid_columnconfigure(0, weight=1)

        self.ip_entry = ctk.CTkEntry(
            self.ip_row,
            fg_color="#1E2022",
            border_color="#2D3035",
            text_color="#ECEFF4",
            placeholder_text="Enter WLED IP (e.g. 192.168.1.100)",
            height=34
        )
        self.ip_entry.insert(0, self.settings.get("wled_ip", "192.168.1.100"))
        self.ip_entry.grid(row=0, column=0, padx=(0, 8), sticky="ew")

        self.test_btn = ctk.CTkButton(
            self.ip_row,
            text="Test Link ⚡",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            fg_color="#4F5D75",
            hover_color="#2D3142",
            text_color="#ECEFF4",
            width=100,
            height=34,
            command=self._on_test_link
        )
        self.test_btn.grid(row=0, column=1, sticky="e")

        # Connection Telemetry Status Box
        self.connection_status_box = ctk.CTkFrame(self.left_frame, fg_color="#1E2022", height=32, corner_radius=6, border_width=1, border_color="#2D3035")
        self.connection_status_box.grid(row=2, column=0, padx=15, pady=(0, 18), sticky="ew")
        self.connection_status_box.grid_propagate(False)
        self.connection_status_box.grid_columnconfigure(0, weight=1)
        self.connection_status_box.grid_rowconfigure(0, weight=1)

        self.connection_lbl = ctk.CTkLabel(
            self.connection_status_box,
            text="STATUS: DISCONNECTED (PENDING TEST)",
            font=ctk.CTkFont(family="Inter", size=10, weight="bold"),
            text_color="#8F949F"
        )
        self.connection_lbl.grid(row=0, column=0)

        # Divider
        self.div_line = ctk.CTkFrame(self.left_frame, height=1, fg_color="#25262B")
        self.div_line.grid(row=3, column=0, padx=15, pady=(0, 15), sticky="ew")

        # 2. Volume Preferences Section
        self.vol_title = ctk.CTkLabel(
            self.left_frame,
            text="GLOBAL SOUND DECK",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            text_color="#4F525A"
        )
        self.vol_title.grid(row=4, column=0, padx=15, pady=(0, 8), sticky="w")

        # Audio overlay volume
        audio_vol = int(self.settings.get("audio_volume", 0.3) * 100)
        self.audio_lbl = ctk.CTkLabel(
            self.left_frame,
            text=f"Neuro-Audio Overlay Volume ({audio_vol}%):",
            font=ctk.CTkFont(family="Inter", size=12),
            text_color="#ECEFF4"
        )
        self.audio_lbl.grid(row=5, column=0, padx=15, pady=(5, 3), sticky="w")

        self.audio_slider = ctk.CTkSlider(
            self.left_frame,
            from_=0,
            to=100,
            number_of_steps=100,
            progress_color="#00FFFF",
            fg_color="#1E2022",
            command=self._on_audio_slider_change
        )
        self.audio_slider.set(audio_vol)
        self.audio_slider.grid(row=6, column=0, padx=15, pady=(0, 12), sticky="ew")

        # Video narration volume
        video_vol = int(self.settings.get("video_volume", 0.8) * 100)
        self.video_lbl = ctk.CTkLabel(
            self.left_frame,
            text=f"Educational Video Volume ({video_vol}%):",
            font=ctk.CTkFont(family="Inter", size=12),
            text_color="#ECEFF4"
        )
        self.video_lbl.grid(row=7, column=0, padx=15, pady=(5, 3), sticky="w")

        self.video_slider = ctk.CTkSlider(
            self.left_frame,
            from_=0,
            to=100,
            number_of_steps=100,
            progress_color="#00FFFF",
            fg_color="#1E2022",
            command=self._on_video_slider_change
        )
        self.video_slider.set(video_vol)
        self.video_slider.grid(row=8, column=0, padx=15, pady=(0, 20), sticky="ew")

        # ----------------------------------------------------
        # Right Workspace Column: Coordinated Lighting Debugger Panel
        # ----------------------------------------------------
        self.right_frame = ctk.CTkFrame(self, fg_color="#161719", corner_radius=8, border_width=1, border_color="#25262B")
        self.right_frame.grid(row=1, column=1, padx=(10, 20), pady=(0, 20), sticky="nsew")
        self.right_frame.grid_columnconfigure(0, weight=1)

        self.debug_title = ctk.CTkLabel(
            self.right_frame,
            text="COORDINATED LIGHTING DEBUGGER",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            text_color="#4F525A"
        )
        self.debug_title.grid(row=0, column=0, padx=15, pady=(15, 5), sticky="w")

        self.debug_desc = ctk.CTkLabel(
            self.right_frame,
            text="Click presets below to manually verify that your Warm White & Cool White COB strip outputs match targets offline.",
            font=ctk.CTkFont(family="Inter", size=11),
            text_color="#5D626E",
            justify="left",
            wraplength=230
        )
        self.debug_desc.grid(row=1, column=0, padx=15, pady=(0, 15), sticky="w")

        #Presest Presets Debug Buttons
        self.focus_dbg = ctk.CTkButton(
            self.right_frame,
            text="💡  Test Focus Presets  (CCT: 220, Bri: 255)",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            fg_color="#FD7E14",
            hover_color="#D9480F",
            text_color="#ECEFF4",
            height=34,
            command=lambda: self.on_send_debug_light(255, 220)
        )
        self.focus_dbg.grid(row=2, column=0, padx=15, pady=6, sticky="ew")

        self.creative_dbg = ctk.CTkButton(
            self.right_frame,
            text="💡  Test Creative Presets  (CCT: 128, Bri: 200)",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            fg_color="#00FFFF",
            hover_color="#09929F",
            text_color="#121315",
            height=34,
            command=lambda: self.on_send_debug_light(200, 128)
        )
        self.creative_dbg.grid(row=3, column=0, padx=15, pady=6, sticky="ew")

        self.calm_dbg = ctk.CTkButton(
            self.right_frame,
            text="💡  Test Calm Presets  (CCT: 30, Bri: 120)",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            fg_color="#40C057",
            hover_color="#2B8A3E",
            text_color="#ECEFF4",
            height=34,
            command=lambda: self.on_send_debug_light(120, 30)
        )
        self.calm_dbg.grid(row=4, column=0, padx=15, pady=6, sticky="ew")

        self.off_dbg = ctk.CTkButton(
            self.right_frame,
            text="❌  Turn Lighting Off  (Bri: 0)",
            font=ctk.CTkFont(family="Inter", size=11, weight="bold"),
            fg_color="#343A40",
            hover_color="#212529",
            text_color="#C1C2C5",
            height=34,
            command=lambda: self.on_send_debug_light(0, 0)
        )
        self.off_dbg.grid(row=5, column=0, padx=15, pady=(6, 20), sticky="ew")

        # ----------------------------------------------------
        # Bottom Save Row (Spans full columns)
        # ----------------------------------------------------
        self.save_frame = ctk.CTkFrame(self, fg_color="transparent")
        self.save_frame.grid(row=2, column=0, columnspan=2, padx=20, pady=(0, 20), sticky="ew")
        
        self.save_btn = ctk.CTkButton(
            self.save_frame,
            text="💾  Save Configurations & Apply Globally",
            font=ctk.CTkFont(family="Inter", size=12, weight="bold"),
            fg_color="#2B8A3E",
            hover_color="#237032",
            text_color="#ECEFF4",
            height=38,
            command=self._on_save_config
        )
        self.save_btn.grid(row=0, column=0, sticky="ew")
        self.save_frame.grid_columnconfigure(0, weight=1)

    def _on_test_link(self) -> None:
        """Pings the WLED IP entered in the entry box."""
        ip = self.ip_entry.get().strip()
        self.connection_lbl.configure(text="STATUS: TESTING PIPELINE...", text_color="#00FFFF")
        self.on_test_connection(ip)

    def update_wled_connection(self, status_msg: str, connected: bool) -> None:
        """Called by parent when WLED link thread returns results."""
        if connected:
            self.connection_lbl.configure(text=f"STATUS: ONLINE ({status_msg.upper()})", text_color="#2F9E44")
            self.connection_status_box.configure(border_color="#2F9E44", fg_color="#142C10")
        else:
            self.connection_lbl.configure(text=f"STATUS: OFFLINE ({status_msg.upper()})", text_color="#E03131")
            self.connection_status_box.configure(border_color="#E03131", fg_color="#2C1010")

    def _on_audio_slider_change(self, val: float) -> None:
        self.audio_lbl.configure(text=f"Neuro-Audio Overlay Volume ({int(val)}%):")

    def _on_video_slider_change(self, val: float) -> None:
        self.video_lbl.configure(text=f"Educational Video Volume ({int(val)}%):")

    def _on_save_config(self) -> None:
        """Compiles active GUI details and dispatches parent save configurations callback."""
        ip = self.ip_entry.get().strip()
        settings_payload = {
            "wled_ip": ip,
            "audio_volume": self.audio_slider.get() / 100.0,
            "video_volume": self.video_slider.get() / 100.0
        }
        self.on_save_settings(settings_payload)
