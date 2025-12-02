"""
GPU Share Client - GUI Application
Main graphical user interface using CustomTkinter
"""
import logging
import threading
import time
from typing import Optional
import webbrowser

logger = logging.getLogger(__name__)

try:
    import customtkinter as ctk
    from PIL import Image, ImageTk
    CTK_AVAILABLE = True
except ImportError:
    CTK_AVAILABLE = False
    logger.error("customtkinter not available")

from .config import APP_NAME, APP_VERSION, STATUS_COLORS
from .gpu_monitor import get_gpu_monitor, GPUInfo
from .system_monitor import get_system_monitor, SystemInfo
from .api_client import get_api_client
from .job_worker import JobWorker, JobStatus


class StatusIndicator(ctk.CTkFrame):
    """Status indicator light"""

    def __init__(self, master, size=20, **kwargs):
        super().__init__(master, width=size, height=size, corner_radius=size//2, **kwargs)
        self.size = size
        self._color = STATUS_COLORS["idle"]
        self._blinking = False
        self._blink_thread = None
        self.configure(fg_color=self._color)

    def set_status(self, status: str, blink: bool = False):
        """Set status color"""
        self._color = STATUS_COLORS.get(status, STATUS_COLORS["idle"])
        self._blinking = blink

        if blink and not self._blink_thread:
            self._blink_thread = threading.Thread(target=self._blink_loop)
            self._blink_thread.daemon = True
            self._blink_thread.start()
        elif not blink:
            self._blinking = False
            self.configure(fg_color=self._color)

    def _blink_loop(self):
        """Blink animation"""
        while self._blinking:
            self.configure(fg_color=self._color)
            time.sleep(0.5)
            if self._blinking:
                self.configure(fg_color="#1F2937")
                time.sleep(0.5)


class GPUStatsFrame(ctk.CTkFrame):
    """GPU Statistics Display"""

    def __init__(self, master, **kwargs):
        super().__init__(master, **kwargs)

        self.configure(fg_color="#1F2937", corner_radius=10)

        # Header
        header = ctk.CTkFrame(self, fg_color="transparent")
        header.pack(fill="x", padx=15, pady=(15, 10))

        ctk.CTkLabel(header, text="GPU Status", font=("Arial", 16, "bold")).pack(side="left")

        # Stats grid
        self.stats_frame = ctk.CTkFrame(self, fg_color="transparent")
        self.stats_frame.pack(fill="both", expand=True, padx=15, pady=(0, 15))

        # GPU Name
        self.gpu_name = ctk.CTkLabel(self.stats_frame, text="Detecting GPU...", font=("Arial", 14))
        self.gpu_name.pack(anchor="w", pady=(0, 10))

        # Stats rows
        self.temp_bar = self._create_stat_row("Temperature", "0°C", "#EF4444")
        self.power_bar = self._create_stat_row("Power", "0W", "#F59E0B")
        self.memory_bar = self._create_stat_row("Memory", "0 / 0 GB", "#3B82F6")
        self.gpu_util_bar = self._create_stat_row("GPU Usage", "0%", "#10B981")
        self.fan_bar = self._create_stat_row("Fan Speed", "0%", "#8B5CF6")

    def _create_stat_row(self, label: str, value: str, color: str):
        """Create a stat row with label, progress bar, and value"""
        row = ctk.CTkFrame(self.stats_frame, fg_color="transparent")
        row.pack(fill="x", pady=5)

        lbl = ctk.CTkLabel(row, text=label, font=("Arial", 12), width=100, anchor="w")
        lbl.pack(side="left")

        bar = ctk.CTkProgressBar(row, width=150, height=12, progress_color=color)
        bar.pack(side="left", padx=10)
        bar.set(0)

        val = ctk.CTkLabel(row, text=value, font=("Arial", 12), width=80, anchor="e")
        val.pack(side="right")

        return {"bar": bar, "value": val}

    def update(self, gpu_info: Optional[GPUInfo]):
        """Update GPU stats display"""
        if not gpu_info:
            self.gpu_name.configure(text="No GPU Detected")
            return

        self.gpu_name.configure(text=f"{gpu_info.name}")

        # Temperature (0-100°C range)
        temp_pct = min(gpu_info.temperature / 100, 1.0)
        self.temp_bar["bar"].set(temp_pct)
        self.temp_bar["value"].configure(text=f"{gpu_info.temperature}°C")

        # Power
        if gpu_info.power_limit > 0:
            power_pct = gpu_info.power_draw / gpu_info.power_limit
        else:
            power_pct = 0
        self.power_bar["bar"].set(min(power_pct, 1.0))
        self.power_bar["value"].configure(text=f"{gpu_info.power_draw:.0f}W")

        # Memory
        mem_pct = gpu_info.memory_percent / 100
        self.memory_bar["bar"].set(mem_pct)
        self.memory_bar["value"].configure(
            text=f"{gpu_info.memory_used/1024:.1f} / {gpu_info.memory_total/1024:.1f} GB"
        )

        # GPU Utilization
        self.gpu_util_bar["bar"].set(gpu_info.gpu_util / 100)
        self.gpu_util_bar["value"].configure(text=f"{gpu_info.gpu_util}%")

        # Fan Speed
        self.fan_bar["bar"].set(gpu_info.fan_speed / 100)
        self.fan_bar["value"].configure(text=f"{gpu_info.fan_speed}%")


class SystemStatsFrame(ctk.CTkFrame):
    """System Statistics Display"""

    def __init__(self, master, **kwargs):
        super().__init__(master, **kwargs)

        self.configure(fg_color="#1F2937", corner_radius=10)

        # Header
        header = ctk.CTkFrame(self, fg_color="transparent")
        header.pack(fill="x", padx=15, pady=(15, 10))

        ctk.CTkLabel(header, text="System Stats", font=("Arial", 16, "bold")).pack(side="left")

        # Stats
        self.stats_frame = ctk.CTkFrame(self, fg_color="transparent")
        self.stats_frame.pack(fill="both", expand=True, padx=15, pady=(0, 15))

        self.cpu_bar = self._create_stat_row("CPU", "0%", "#3B82F6")
        self.ram_bar = self._create_stat_row("RAM", "0 / 0 GB", "#10B981")
        self.net_up = self._create_stat_row("Upload", "0 MB/s", "#8B5CF6")
        self.net_down = self._create_stat_row("Download", "0 MB/s", "#F59E0B")

        # Power estimation
        self.power_frame = ctk.CTkFrame(self.stats_frame, fg_color="#374151", corner_radius=8)
        self.power_frame.pack(fill="x", pady=(10, 0))

        power_inner = ctk.CTkFrame(self.power_frame, fg_color="transparent")
        power_inner.pack(padx=10, pady=8)

        ctk.CTkLabel(power_inner, text="Est. Power:", font=("Arial", 11)).pack(side="left")
        self.power_label = ctk.CTkLabel(power_inner, text="0W", font=("Arial", 11, "bold"))
        self.power_label.pack(side="left", padx=5)

        ctk.CTkLabel(power_inner, text="Cost:", font=("Arial", 11)).pack(side="left", padx=(10, 0))
        self.cost_label = ctk.CTkLabel(power_inner, text="฿0.00/hr", font=("Arial", 11, "bold"), text_color="#10B981")
        self.cost_label.pack(side="left", padx=5)

    def _create_stat_row(self, label: str, value: str, color: str):
        """Create a stat row"""
        row = ctk.CTkFrame(self.stats_frame, fg_color="transparent")
        row.pack(fill="x", pady=3)

        lbl = ctk.CTkLabel(row, text=label, font=("Arial", 12), width=80, anchor="w")
        lbl.pack(side="left")

        bar = ctk.CTkProgressBar(row, width=120, height=10, progress_color=color)
        bar.pack(side="left", padx=10)
        bar.set(0)

        val = ctk.CTkLabel(row, text=value, font=("Arial", 11), width=100, anchor="e")
        val.pack(side="right")

        return {"bar": bar, "value": val}

    def update(self, system_info: Optional[SystemInfo], gpu_power: float = 0):
        """Update system stats display"""
        if not system_info:
            return

        # CPU
        self.cpu_bar["bar"].set(system_info.cpu_percent / 100)
        self.cpu_bar["value"].configure(text=f"{system_info.cpu_percent:.1f}%")

        # RAM
        self.ram_bar["bar"].set(system_info.ram_percent / 100)
        self.ram_bar["value"].configure(
            text=f"{system_info.ram_used/1024:.1f} / {system_info.ram_total/1024:.1f} GB"
        )

        # Network
        self.net_up["bar"].set(min(system_info.net_speed_up / 10, 1.0))
        self.net_up["value"].configure(text=f"{system_info.net_speed_up:.2f} MB/s")

        self.net_down["bar"].set(min(system_info.net_speed_down / 10, 1.0))
        self.net_down["value"].configure(text=f"{system_info.net_speed_down:.2f} MB/s")

        # Power estimation
        from .system_monitor import get_system_monitor
        total_power, cost_per_hour = get_system_monitor().estimate_power_consumption(gpu_power)
        self.power_label.configure(text=f"{total_power:.0f}W")
        self.cost_label.configure(text=f"฿{cost_per_hour:.2f}/hr")


class EarningsFrame(ctk.CTkFrame):
    """Earnings Display"""

    def __init__(self, master, **kwargs):
        super().__init__(master, **kwargs)

        self.configure(fg_color="#1F2937", corner_radius=10)

        # Header
        header = ctk.CTkFrame(self, fg_color="transparent")
        header.pack(fill="x", padx=15, pady=(15, 10))

        ctk.CTkLabel(header, text="Earnings", font=("Arial", 16, "bold")).pack(side="left")

        # Balance
        balance_frame = ctk.CTkFrame(self, fg_color="#374151", corner_radius=8)
        balance_frame.pack(fill="x", padx=15, pady=(0, 10))

        ctk.CTkLabel(balance_frame, text="Session Earnings", font=("Arial", 11)).pack(pady=(10, 0))
        self.balance_label = ctk.CTkLabel(balance_frame, text="$0.00", font=("Arial", 28, "bold"), text_color="#10B981")
        self.balance_label.pack(pady=(0, 10))

        # Stats
        stats = ctk.CTkFrame(self, fg_color="transparent")
        stats.pack(fill="x", padx=15, pady=(0, 15))

        # Jobs completed
        job_frame = ctk.CTkFrame(stats, fg_color="#374151", corner_radius=8)
        job_frame.pack(side="left", fill="both", expand=True, padx=(0, 5))

        ctk.CTkLabel(job_frame, text="Jobs Done", font=("Arial", 10)).pack(pady=(8, 0))
        self.jobs_label = ctk.CTkLabel(job_frame, text="0", font=("Arial", 18, "bold"))
        self.jobs_label.pack(pady=(0, 8))

        # Jobs failed
        fail_frame = ctk.CTkFrame(stats, fg_color="#374151", corner_radius=8)
        fail_frame.pack(side="right", fill="both", expand=True, padx=(5, 0))

        ctk.CTkLabel(fail_frame, text="Failed", font=("Arial", 10)).pack(pady=(8, 0))
        self.failed_label = ctk.CTkLabel(fail_frame, text="0", font=("Arial", 18, "bold"), text_color="#EF4444")
        self.failed_label.pack(pady=(0, 8))

    def update(self, earned: float, jobs_done: int, jobs_failed: int):
        """Update earnings display"""
        self.balance_label.configure(text=f"${earned:.4f}")
        self.jobs_label.configure(text=str(jobs_done))
        self.failed_label.configure(text=str(jobs_failed))


class SettingsFrame(ctk.CTkFrame):
    """Settings Panel"""

    def __init__(self, master, on_save=None, **kwargs):
        super().__init__(master, **kwargs)

        self.on_save = on_save
        self.configure(fg_color="#1F2937", corner_radius=10)

        # Header
        ctk.CTkLabel(self, text="Settings", font=("Arial", 16, "bold")).pack(padx=15, pady=15, anchor="w")

        settings = ctk.CTkFrame(self, fg_color="transparent")
        settings.pack(fill="both", expand=True, padx=15, pady=(0, 15))

        # Temperature Limit
        temp_frame = ctk.CTkFrame(settings, fg_color="transparent")
        temp_frame.pack(fill="x", pady=5)

        ctk.CTkLabel(temp_frame, text="Max Temperature:", width=120, anchor="w").pack(side="left")
        self.temp_slider = ctk.CTkSlider(temp_frame, from_=60, to=90, width=150)
        self.temp_slider.set(85)
        self.temp_slider.pack(side="left", padx=10)
        self.temp_label = ctk.CTkLabel(temp_frame, text="85°C", width=50)
        self.temp_label.pack(side="left")
        self.temp_slider.configure(command=lambda v: self.temp_label.configure(text=f"{int(v)}°C"))

        # Fan Speed
        fan_frame = ctk.CTkFrame(settings, fg_color="transparent")
        fan_frame.pack(fill="x", pady=5)

        ctk.CTkLabel(fan_frame, text="Fan Speed:", width=120, anchor="w").pack(side="left")
        self.fan_slider = ctk.CTkSlider(fan_frame, from_=0, to=100, width=150)
        self.fan_slider.set(0)
        self.fan_slider.pack(side="left", padx=10)
        self.fan_label = ctk.CTkLabel(fan_frame, text="Auto", width=50)
        self.fan_label.pack(side="left")
        self.fan_slider.configure(command=self._update_fan_label)

        # Auto-start
        self.auto_start = ctk.CTkCheckBox(settings, text="Start mining on launch")
        self.auto_start.pack(anchor="w", pady=5)

        # Save button
        ctk.CTkButton(
            settings, text="Save Settings", command=self._save_settings,
            fg_color="#8B5CF6", hover_color="#7C3AED"
        ).pack(pady=(15, 0))

    def _update_fan_label(self, value):
        if value == 0:
            self.fan_label.configure(text="Auto")
        else:
            self.fan_label.configure(text=f"{int(value)}%")

    def _save_settings(self):
        if self.on_save:
            self.on_save({
                'temp_limit': int(self.temp_slider.get()),
                'fan_speed': int(self.fan_slider.get()),
                'auto_start': self.auto_start.get(),
            })


class MainWindow(ctk.CTk):
    """Main Application Window"""

    def __init__(self):
        super().__init__()

        self.title(f"{APP_NAME} v{APP_VERSION}")
        self.geometry("900x650")
        self.minsize(800, 600)

        # Set dark theme
        ctk.set_appearance_mode("dark")
        ctk.set_default_color_theme("blue")

        self.configure(fg_color="#111827")

        # Initialize components
        self.gpu_monitor = get_gpu_monitor()
        self.system_monitor = get_system_monitor()
        self.api_client = get_api_client()
        self.job_worker = None

        self._running = False
        self._update_thread = None

        self._create_ui()
        self._start_updates()

    def _create_ui(self):
        """Create the UI"""
        # Header
        header = ctk.CTkFrame(self, fg_color="#1F2937", height=60, corner_radius=0)
        header.pack(fill="x")
        header.pack_propagate(False)

        header_inner = ctk.CTkFrame(header, fg_color="transparent")
        header_inner.pack(fill="both", expand=True, padx=20)

        # Logo and title
        title_frame = ctk.CTkFrame(header_inner, fg_color="transparent")
        title_frame.pack(side="left", fill="y")

        ctk.CTkLabel(
            title_frame, text="⚡ GPU Share",
            font=("Arial", 20, "bold"), text_color="#A855F7"
        ).pack(side="left", pady=15)

        ctk.CTkLabel(
            title_frame, text=f"v{APP_VERSION}",
            font=("Arial", 10), text_color="#6B7280"
        ).pack(side="left", padx=10, pady=18)

        # Status indicator
        status_frame = ctk.CTkFrame(header_inner, fg_color="transparent")
        status_frame.pack(side="left", padx=30)

        self.status_light = StatusIndicator(status_frame, size=16)
        self.status_light.pack(side="left")

        self.status_label = ctk.CTkLabel(status_frame, text="Idle", font=("Arial", 12))
        self.status_label.pack(side="left", padx=10)

        # Control buttons
        btn_frame = ctk.CTkFrame(header_inner, fg_color="transparent")
        btn_frame.pack(side="right")

        self.start_btn = ctk.CTkButton(
            btn_frame, text="▶ Start Mining", width=140, height=36,
            fg_color="#10B981", hover_color="#059669",
            command=self._toggle_mining
        )
        self.start_btn.pack(side="left", padx=5)

        ctk.CTkButton(
            btn_frame, text="Dashboard", width=100, height=36,
            fg_color="#3B82F6", hover_color="#2563EB",
            command=lambda: webbrowser.open("http://localhost/gpu-sharing-platform/public/dashboard")
        ).pack(side="left", padx=5)

        # Main content
        content = ctk.CTkFrame(self, fg_color="transparent")
        content.pack(fill="both", expand=True, padx=20, pady=20)

        # Left column
        left_col = ctk.CTkFrame(content, fg_color="transparent")
        left_col.pack(side="left", fill="both", expand=True, padx=(0, 10))

        self.gpu_stats = GPUStatsFrame(left_col)
        self.gpu_stats.pack(fill="x", pady=(0, 10))

        self.system_stats = SystemStatsFrame(left_col)
        self.system_stats.pack(fill="x")

        # Right column
        right_col = ctk.CTkFrame(content, fg_color="transparent", width=280)
        right_col.pack(side="right", fill="y", padx=(10, 0))
        right_col.pack_propagate(False)

        self.earnings = EarningsFrame(right_col)
        self.earnings.pack(fill="x", pady=(0, 10))

        self.settings = SettingsFrame(right_col, on_save=self._save_settings)
        self.settings.pack(fill="x")

        # Footer with warning
        footer = ctk.CTkFrame(self, fg_color="#374151", height=40, corner_radius=0)
        footer.pack(fill="x", side="bottom")

        self.footer_label = ctk.CTkLabel(
            footer,
            text="Ready to mine. Click 'Start Mining' to begin earning.",
            font=("Arial", 11)
        )
        self.footer_label.pack(pady=10)

    def _start_updates(self):
        """Start background update thread"""
        self._update_thread = threading.Thread(target=self._update_loop)
        self._update_thread.daemon = True
        self._update_thread.start()

    def _update_loop(self):
        """Background update loop"""
        while True:
            try:
                # Update GPU stats
                gpu_info = self.gpu_monitor.get_gpu_info(0)
                self.after(0, lambda: self.gpu_stats.update(gpu_info))

                # Update system stats
                system_info = self.system_monitor.get_system_info()
                gpu_power = gpu_info.power_draw if gpu_info else 0
                self.after(0, lambda: self.system_stats.update(system_info, gpu_power))

                # Update earnings if mining
                if self.job_worker:
                    stats = self.job_worker.get_stats()
                    self.after(0, lambda: self.earnings.update(
                        stats['total_earned'],
                        stats['jobs_completed'],
                        stats['jobs_failed']
                    ))

                time.sleep(1)

            except Exception as e:
                logger.error(f"Update error: {e}")
                time.sleep(5)

    def _toggle_mining(self):
        """Toggle mining on/off"""
        if self._running:
            self._stop_mining()
        else:
            self._start_mining()

    def _start_mining(self):
        """Start mining"""
        self._running = True
        self.start_btn.configure(text="⏹ Stop Mining", fg_color="#EF4444", hover_color="#DC2626")
        self.status_light.set_status("idle", blink=True)
        self.status_label.configure(text="Waiting for jobs...")
        self.footer_label.configure(text="Mining active. Waiting for jobs...")

        # Initialize job worker
        self.job_worker = JobWorker(self.api_client, self.gpu_monitor, self.system_monitor)
        self.job_worker.set_callbacks(
            on_status_change=self._on_status_change,
            on_job_received=self._on_job_received,
            on_job_completed=self._on_job_completed,
            on_job_failed=self._on_job_failed,
        )
        self.job_worker.set_limits(
            temp_limit=int(self.settings.temp_slider.get()),
            gpu_usage_limit=90
        )
        self.job_worker.start()

    def _stop_mining(self):
        """Stop mining"""
        self._running = False
        self.start_btn.configure(text="▶ Start Mining", fg_color="#10B981", hover_color="#059669")
        self.status_light.set_status("idle", blink=False)
        self.status_label.configure(text="Idle")
        self.footer_label.configure(text="Mining stopped.")

        if self.job_worker:
            self.job_worker.stop()
            self.job_worker = None

    def _on_status_change(self, status: JobStatus):
        """Handle status change"""
        status_text = {
            JobStatus.IDLE: "Waiting for jobs...",
            JobStatus.RECEIVING: "Receiving job...",
            JobStatus.PROCESSING: "Processing...",
            JobStatus.UPLOADING: "Uploading result...",
            JobStatus.FAILED: "Job failed",
        }

        self.after(0, lambda: [
            self.status_light.set_status(status.value, blink=status != JobStatus.IDLE),
            self.status_label.configure(text=status_text.get(status, "Unknown")),
        ])

    def _on_job_received(self, job):
        """Handle job received"""
        self.after(0, lambda: self.footer_label.configure(
            text=f"Received job: {job.job_type} - {job.job_id}"
        ))

    def _on_job_completed(self, job):
        """Handle job completed"""
        self.after(0, lambda: self.footer_label.configure(
            text=f"Job completed: {job.job_id}"
        ))

    def _on_job_failed(self, job, error):
        """Handle job failed"""
        self.after(0, lambda: self.footer_label.configure(
            text=f"Job failed: {error}. Check GPU/internet connection."
        ))

    def _save_settings(self, settings):
        """Save settings"""
        if self.job_worker:
            self.job_worker.set_limits(temp_limit=settings['temp_limit'])

        # Apply fan speed
        if settings['fan_speed'] > 0:
            self.gpu_monitor.set_fan_speed(0, settings['fan_speed'])

        self.footer_label.configure(text="Settings saved.")


def run_app():
    """Run the application"""
    if not CTK_AVAILABLE:
        print("CustomTkinter not available. Please install: pip install customtkinter")
        return

    app = MainWindow()
    app.mainloop()


if __name__ == "__main__":
    run_app()
