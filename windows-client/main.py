"""
GPU Sharing Platform - Windows Client
Main entry point for the GPU worker application
© 2025 Xman Studio Thailand. All rights reserved.
"""
import sys
import os
import logging
import threading
import time
import json
from pathlib import Path
from typing import Dict

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent))

from PyQt6.QtWidgets import (
    QApplication, QMainWindow, QWidget, QVBoxLayout, QHBoxLayout,
    QLabel, QPushButton, QLineEdit, QTextEdit, QProgressBar,
    QTabWidget, QGroupBox, QFormLayout, QMessageBox, QStackedWidget,
    QFrame, QSlider, QSpinBox, QScrollArea, QListWidget, QListWidgetItem
)
from PyQt6.QtCore import Qt, QTimer, pyqtSignal, QObject
from PyQt6.QtGui import QFont, QPalette, QColor

from config import CLIENT_VERSION, HEARTBEAT_INTERVAL, WORK_POLL_INTERVAL
from api_client import APIClient, APIError
from hardware_info import HardwareInfo
from worker import GPUWorker
from model_manager import ModelManager

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('gpu_client.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)


class WorkerSignals(QObject):
    """Signals for worker thread communication"""
    log_message = pyqtSignal(str)
    status_update = pyqtSignal(str)
    progress_update = pyqtSignal(int)
    earnings_update = pyqtSignal(dict)
    work_completed = pyqtSignal(dict)
    error = pyqtSignal(str)
    connection_status = pyqtSignal(bool)
    # Model download signals
    model_download_progress = pyqtSignal(str, str, int, str)  # model_id, status, progress, error
    models_updated = pyqtSignal()


class GPUClientApp(QMainWindow):
    def __init__(self):
        super().__init__()

        self.api = APIClient()
        self.hardware = HardwareInfo()
        self.worker = GPUWorker()
        self.model_manager = ModelManager()
        self.signals = WorkerSignals()

        self.node_id = None
        self.is_running = False
        self.user_data = None
        self.is_connected = False
        self.gpu_power_limit = 100  # Default 100%
        self.available_models = []  # Models available for download

        self.setup_ui()
        self.setup_signals()
        self.setup_timers()

        self.load_saved_settings()
        self.load_saved_token()

        # Setup model manager callback
        self.model_manager.set_progress_callback(self.on_model_download_progress)

    def setup_ui(self):
        """Setup the user interface"""
        self.setWindowTitle(f'GPU Share v{CLIENT_VERSION} - Xman Studio Thailand')
        self.setMinimumSize(900, 700)

        # Dark theme
        self.setStyleSheet("""
            QMainWindow { background-color: #0f0f1a; }
            QWidget { color: #eee; font-family: 'Segoe UI', Arial; }
            QGroupBox {
                border: 1px solid #4a4a6a;
                border-radius: 12px;
                margin-top: 12px;
                padding: 20px;
                background-color: rgba(22, 33, 62, 0.8);
            }
            QGroupBox::title {
                subcontrol-origin: margin;
                left: 15px;
                padding: 0 8px;
                color: #a855f7;
                font-weight: bold;
            }
            QPushButton {
                background-color: #7c3aed;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: bold;
            }
            QPushButton:hover { background-color: #8b5cf6; }
            QPushButton:pressed { background-color: #6d28d9; }
            QPushButton:disabled { background-color: #4a4a6a; color: #888; }
            QLineEdit {
                background-color: rgba(15, 52, 96, 0.8);
                border: 1px solid #4a4a6a;
                border-radius: 8px;
                padding: 10px;
            }
            QLineEdit:focus { border-color: #a855f7; }
            QTextEdit {
                background-color: rgba(15, 52, 96, 0.8);
                border: 1px solid #4a4a6a;
                border-radius: 8px;
            }
            QProgressBar {
                border: none;
                border-radius: 6px;
                background-color: rgba(15, 52, 96, 0.8);
                text-align: center;
                height: 24px;
            }
            QProgressBar::chunk {
                background: qlineargradient(x1:0, y1:0, x2:1, y2:0, stop:0 #a855f7, stop:1 #ec4899);
                border-radius: 6px;
            }
            QTabWidget::pane {
                border: 1px solid #4a4a6a;
                border-radius: 12px;
                background-color: rgba(22, 33, 62, 0.8);
            }
            QTabBar::tab {
                background-color: rgba(15, 52, 96, 0.8);
                padding: 12px 24px;
                margin-right: 4px;
                border-top-left-radius: 8px;
                border-top-right-radius: 8px;
            }
            QTabBar::tab:selected { background-color: #7c3aed; }
            QSlider::groove:horizontal {
                border: none;
                height: 8px;
                background: rgba(15, 52, 96, 0.8);
                border-radius: 4px;
            }
            QSlider::handle:horizontal {
                background: #a855f7;
                border: none;
                width: 20px;
                margin: -6px 0;
                border-radius: 10px;
            }
            QSlider::sub-page:horizontal {
                background: qlineargradient(x1:0, y1:0, x2:1, y2:0, stop:0 #a855f7, stop:1 #ec4899);
                border-radius: 4px;
            }
        """)

        # Main widget
        main_widget = QWidget()
        self.setCentralWidget(main_widget)
        layout = QVBoxLayout(main_widget)
        layout.setContentsMargins(20, 20, 20, 20)
        layout.setSpacing(15)

        # Stacked widget for login/main views
        self.stack = QStackedWidget()
        layout.addWidget(self.stack)

        # Login page
        self.login_page = self.create_login_page()
        self.stack.addWidget(self.login_page)

        # Main page
        self.main_page = self.create_main_page()
        self.stack.addWidget(self.main_page)

    def create_login_page(self) -> QWidget:
        """Create login/register page"""
        page = QWidget()
        layout = QVBoxLayout(page)
        layout.setAlignment(Qt.AlignmentFlag.AlignCenter)

        # Logo/Title
        title = QLabel('GPU Share')
        title.setFont(QFont('Segoe UI', 32, QFont.Weight.Bold))
        title.setStyleSheet('color: #a855f7;')
        title.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(title)

        subtitle = QLabel('Share your GPU power, earn rewards')
        subtitle.setStyleSheet('color: #888; font-size: 14px;')
        subtitle.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(subtitle)

        copyright_label = QLabel('© 2025 Xman Studio Thailand')
        copyright_label.setStyleSheet('color: #666; font-size: 11px;')
        copyright_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(copyright_label)

        layout.addSpacing(40)

        # Login form
        form_group = QGroupBox('Login to Your Account')
        form_layout = QFormLayout()

        self.email_input = QLineEdit()
        self.email_input.setPlaceholderText('Enter your email')
        form_layout.addRow('Email:', self.email_input)

        self.password_input = QLineEdit()
        self.password_input.setPlaceholderText('Enter your password')
        self.password_input.setEchoMode(QLineEdit.EchoMode.Password)
        form_layout.addRow('Password:', self.password_input)

        form_group.setLayout(form_layout)
        form_group.setMaximumWidth(450)
        layout.addWidget(form_group, alignment=Qt.AlignmentFlag.AlignCenter)

        # Buttons
        btn_layout = QHBoxLayout()

        login_btn = QPushButton('Login')
        login_btn.setStyleSheet('background-color: #22c55e;')
        login_btn.clicked.connect(self.on_login)
        btn_layout.addWidget(login_btn)

        register_btn = QPushButton('Register')
        register_btn.setStyleSheet('background-color: #3b82f6;')
        register_btn.clicked.connect(self.on_register)
        btn_layout.addWidget(register_btn)

        layout.addLayout(btn_layout)

        return page

    def create_main_page(self) -> QWidget:
        """Create main dashboard page"""
        page = QWidget()
        layout = QVBoxLayout(page)

        # Header with connection status
        header = QHBoxLayout()

        self.user_label = QLabel('Not logged in')
        self.user_label.setFont(QFont('Segoe UI', 12))
        header.addWidget(self.user_label)

        header.addStretch()

        # Connection status indicator
        self.connection_indicator = QLabel('● Disconnected')
        self.connection_indicator.setStyleSheet('color: #888; font-weight: bold;')
        header.addWidget(self.connection_indicator)

        header.addSpacing(20)

        logout_btn = QPushButton('Logout')
        logout_btn.setStyleSheet('background-color: #dc2626;')
        logout_btn.clicked.connect(self.on_logout)
        header.addWidget(logout_btn)

        layout.addLayout(header)

        # Tabs
        tabs = QTabWidget()

        # Dashboard tab
        dashboard = self.create_dashboard_tab()
        tabs.addTab(dashboard, '🖥️ Dashboard')

        # Models tab - NEW
        models_tab = self.create_models_tab()
        tabs.addTab(models_tab, '🤖 Models')

        # Settings tab
        settings = self.create_settings_tab()
        tabs.addTab(settings, '⚙️ Settings')

        # Earnings tab
        earnings = self.create_earnings_tab()
        tabs.addTab(earnings, '💰 Earnings')

        # Logs tab
        logs = self.create_logs_tab()
        tabs.addTab(logs, '📋 Logs')

        layout.addWidget(tabs)

        # Footer
        footer = QLabel('© 2025 Xman Studio Thailand. All rights reserved.')
        footer.setStyleSheet('color: #666; font-size: 10px;')
        footer.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(footer)

        return page

    def create_dashboard_tab(self) -> QWidget:
        """Create dashboard tab content"""
        tab = QWidget()
        layout = QVBoxLayout(tab)

        # Top row - GPU Info and Status
        top_row = QHBoxLayout()

        # GPU Info
        gpu_group = QGroupBox('GPU Information')
        gpu_layout = QFormLayout()

        system_info = self.hardware.get_system_info()
        self.gpu_model_label = QLabel(system_info['gpu_model'])
        self.gpu_model_label.setStyleSheet('color: #a855f7; font-weight: bold;')
        gpu_layout.addRow('GPU Model:', self.gpu_model_label)

        self.gpu_vram_label = QLabel(f"{system_info['gpu_vram_mb']} MB")
        gpu_layout.addRow('VRAM:', self.gpu_vram_label)

        self.node_id_label = QLabel('Not registered')
        self.node_id_label.setStyleSheet('font-family: monospace;')
        gpu_layout.addRow('Node ID:', self.node_id_label)

        self.status_label = QLabel('Offline')
        self.status_label.setStyleSheet('color: #888; font-weight: bold;')
        gpu_layout.addRow('Status:', self.status_label)

        self.rank_label = QLabel('--')
        self.rank_label.setStyleSheet('color: #ffd700;')
        gpu_layout.addRow('Rank:', self.rank_label)

        gpu_group.setLayout(gpu_layout)
        top_row.addWidget(gpu_group)

        # Stats
        stats_group = QGroupBox('Session Stats')
        stats_layout = QFormLayout()

        self.jobs_completed_label = QLabel('0')
        self.jobs_completed_label.setStyleSheet('color: #22c55e; font-size: 18px; font-weight: bold;')
        stats_layout.addRow('Jobs Completed:', self.jobs_completed_label)

        self.session_earnings_label = QLabel('$0.00')
        self.session_earnings_label.setStyleSheet('color: #22c55e; font-size: 18px; font-weight: bold;')
        stats_layout.addRow('Session Earnings:', self.session_earnings_label)

        self.uptime_label = QLabel('0:00:00')
        stats_layout.addRow('Uptime:', self.uptime_label)

        stats_group.setLayout(stats_layout)
        top_row.addWidget(stats_group)

        layout.addLayout(top_row)

        # Work Progress
        work_group = QGroupBox('Current Work')
        work_layout = QVBoxLayout()

        self.work_info_label = QLabel('No active work')
        self.work_info_label.setStyleSheet('font-size: 14px;')
        work_layout.addWidget(self.work_info_label)

        self.progress_bar = QProgressBar()
        self.progress_bar.setRange(0, 100)
        self.progress_bar.setValue(0)
        work_layout.addWidget(self.progress_bar)

        work_group.setLayout(work_layout)
        layout.addWidget(work_group)

        # Controls
        control_group = QGroupBox('Mining Controls')
        control_layout = QHBoxLayout()

        self.start_btn = QPushButton('▶ Start Mining')
        self.start_btn.setStyleSheet('background-color: #22c55e; font-size: 14px; padding: 15px;')
        self.start_btn.clicked.connect(self.on_start)
        control_layout.addWidget(self.start_btn)

        self.stop_btn = QPushButton('⏹ Stop')
        self.stop_btn.setStyleSheet('background-color: #dc2626; font-size: 14px; padding: 15px;')
        self.stop_btn.setEnabled(False)
        self.stop_btn.clicked.connect(self.on_stop)
        control_layout.addWidget(self.stop_btn)

        benchmark_btn = QPushButton('📊 Run Benchmark')
        benchmark_btn.setStyleSheet('font-size: 14px; padding: 15px;')
        benchmark_btn.clicked.connect(self.on_benchmark)
        control_layout.addWidget(benchmark_btn)

        control_group.setLayout(control_layout)
        layout.addWidget(control_group)

        layout.addStretch()

        return tab

    def create_settings_tab(self) -> QWidget:
        """Create settings tab content"""
        tab = QWidget()
        layout = QVBoxLayout(tab)

        # GPU Power Settings
        power_group = QGroupBox('GPU Power Settings')
        power_layout = QVBoxLayout()

        power_info = QLabel('Adjust how much GPU power to share. Lower values use less power but earn less.')
        power_info.setStyleSheet('color: #888;')
        power_info.setWordWrap(True)
        power_layout.addWidget(power_info)

        slider_layout = QHBoxLayout()

        self.power_slider = QSlider(Qt.Orientation.Horizontal)
        self.power_slider.setMinimum(10)
        self.power_slider.setMaximum(100)
        self.power_slider.setValue(self.gpu_power_limit)
        self.power_slider.setTickInterval(10)
        self.power_slider.setTickPosition(QSlider.TickPosition.TicksBelow)
        self.power_slider.valueChanged.connect(self.on_power_changed)
        slider_layout.addWidget(self.power_slider)

        self.power_label = QLabel(f'{self.gpu_power_limit}%')
        self.power_label.setStyleSheet('font-size: 18px; font-weight: bold; color: #a855f7; min-width: 60px;')
        slider_layout.addWidget(self.power_label)

        power_layout.addLayout(slider_layout)

        presets_layout = QHBoxLayout()
        for pct in [25, 50, 75, 100]:
            btn = QPushButton(f'{pct}%')
            btn.setStyleSheet('padding: 8px 16px;')
            btn.clicked.connect(lambda checked, p=pct: self.set_power(p))
            presets_layout.addWidget(btn)
        power_layout.addLayout(presets_layout)

        power_group.setLayout(power_layout)
        layout.addWidget(power_group)

        # Connection Settings
        conn_group = QGroupBox('Connection')
        conn_layout = QFormLayout()

        self.sync_status_label = QLabel('Not synced')
        self.sync_status_label.setStyleSheet('color: #888;')
        conn_layout.addRow('Sync Status:', self.sync_status_label)

        self.last_sync_label = QLabel('Never')
        conn_layout.addRow('Last Sync:', self.last_sync_label)

        sync_btn = QPushButton('🔄 Sync Now')
        sync_btn.clicked.connect(self.manual_sync)
        conn_layout.addRow('', sync_btn)

        conn_group.setLayout(conn_layout)
        layout.addWidget(conn_group)

        # Auto-start
        auto_group = QGroupBox('Startup Options')
        auto_layout = QVBoxLayout()

        self.auto_start_checkbox = QPushButton('Enable Auto-Start on Windows Boot')
        self.auto_start_checkbox.setCheckable(True)
        self.auto_start_checkbox.setStyleSheet('''
            QPushButton { background-color: #4a4a6a; }
            QPushButton:checked { background-color: #22c55e; }
        ''')
        auto_layout.addWidget(self.auto_start_checkbox)

        self.auto_mine_checkbox = QPushButton('Auto-Start Mining on Launch')
        self.auto_mine_checkbox.setCheckable(True)
        self.auto_mine_checkbox.setStyleSheet('''
            QPushButton { background-color: #4a4a6a; }
            QPushButton:checked { background-color: #22c55e; }
        ''')
        auto_layout.addWidget(self.auto_mine_checkbox)

        auto_group.setLayout(auto_layout)
        layout.addWidget(auto_group)

        layout.addStretch()

        return tab

    def create_earnings_tab(self) -> QWidget:
        """Create earnings tab content"""
        tab = QWidget()
        layout = QVBoxLayout(tab)

        # Summary
        summary_group = QGroupBox('Earnings Summary')
        summary_layout = QFormLayout()

        self.balance_label = QLabel('$0.00')
        self.balance_label.setFont(QFont('Segoe UI', 24, QFont.Weight.Bold))
        self.balance_label.setStyleSheet('color: #22c55e;')
        summary_layout.addRow('Available Balance:', self.balance_label)

        self.pending_label = QLabel('$0.00')
        self.pending_label.setStyleSheet('color: #eab308; font-size: 16px;')
        summary_layout.addRow('Pending:', self.pending_label)

        self.total_earned_label = QLabel('$0.00')
        self.total_earned_label.setStyleSheet('color: #a855f7; font-size: 16px;')
        summary_layout.addRow('Total Earned:', self.total_earned_label)

        summary_group.setLayout(summary_layout)
        layout.addWidget(summary_group)

        # Referral info
        ref_group = QGroupBox('Referral Earnings')
        ref_layout = QFormLayout()

        self.referral_earnings_label = QLabel('$0.00')
        self.referral_earnings_label.setStyleSheet('color: #3b82f6; font-size: 16px;')
        ref_layout.addRow('From Referrals:', self.referral_earnings_label)

        self.team_count_label = QLabel('0')
        ref_layout.addRow('Team Members:', self.team_count_label)

        ref_group.setLayout(ref_layout)
        layout.addWidget(ref_group)

        # Refresh button
        refresh_btn = QPushButton('🔄 Refresh Earnings')
        refresh_btn.clicked.connect(self.refresh_earnings)
        layout.addWidget(refresh_btn)

        layout.addStretch()

        return tab

    def create_logs_tab(self) -> QWidget:
        """Create logs tab content"""
        tab = QWidget()
        layout = QVBoxLayout(tab)

        self.log_text = QTextEdit()
        self.log_text.setReadOnly(True)
        self.log_text.setFont(QFont('Consolas', 10))
        layout.addWidget(self.log_text)

        btn_layout = QHBoxLayout()

        clear_btn = QPushButton('Clear Logs')
        clear_btn.clicked.connect(lambda: self.log_text.clear())
        btn_layout.addWidget(clear_btn)

        save_btn = QPushButton('Save Logs')
        save_btn.clicked.connect(self.save_logs)
        btn_layout.addWidget(save_btn)

        layout.addLayout(btn_layout)

        return tab

    def create_models_tab(self) -> QWidget:
        """Create models management tab with sci-fi toggle switches"""
        tab = QWidget()
        layout = QVBoxLayout(tab)

        # Info section with sci-fi styling
        info_group = QGroupBox('🚀 AI Models Control Panel')
        info_layout = QVBoxLayout()

        info_text = QLabel(
            '⚡ ดาวน์โหลดโมเดล AI ไว้ล่วงหน้าเพื่อรับงานได้เร็วขึ้น\n'
            '🎯 เปิด/ปิดสวิตช์เพื่อเลือกโมเดลที่ต้องการรับงาน'
        )
        info_text.setStyleSheet('color: #888; font-size: 12px;')
        info_text.setWordWrap(True)
        info_layout.addWidget(info_text)

        # Stats bar
        stats_layout = QHBoxLayout()
        self.models_count_label = QLabel('0 models')
        self.models_count_label.setStyleSheet('font-size: 14px; font-weight: bold; color: #22c55e;')
        stats_layout.addWidget(QLabel('🤖 Installed:'))
        stats_layout.addWidget(self.models_count_label)
        stats_layout.addStretch()
        self.models_active_label = QLabel('0 active')
        self.models_active_label.setStyleSheet('font-size: 14px; font-weight: bold; color: #3b82f6;')
        stats_layout.addWidget(QLabel('⚡ Active:'))
        stats_layout.addWidget(self.models_active_label)
        stats_layout.addStretch()
        self.models_size_label = QLabel('0 MB')
        self.models_size_label.setStyleSheet('font-size: 14px; color: #888;')
        stats_layout.addWidget(QLabel('💾 Size:'))
        stats_layout.addWidget(self.models_size_label)
        info_layout.addLayout(stats_layout)
        info_group.setLayout(info_layout)
        layout.addWidget(info_group)

        # Refresh button row
        btn_layout = QHBoxLayout()
        refresh_models_btn = QPushButton('🔄 Refresh Models')
        refresh_models_btn.clicked.connect(self.refresh_available_models)
        btn_layout.addWidget(refresh_models_btn)
        btn_layout.addStretch()
        layout.addLayout(btn_layout)

        # Scroll area for model cards
        scroll_area = QScrollArea()
        scroll_area.setWidgetResizable(True)
        scroll_area.setStyleSheet('''
            QScrollArea {
                background-color: transparent;
                border: none;
            }
            QScrollBar:vertical {
                background: rgba(15, 52, 96, 0.5);
                width: 12px;
                border-radius: 6px;
            }
            QScrollBar::handle:vertical {
                background: #7c3aed;
                border-radius: 6px;
                min-height: 30px;
            }
        ''')

        # Container for model cards
        self.models_container = QWidget()
        self.models_layout = QVBoxLayout(self.models_container)
        self.models_layout.setSpacing(10)

        # Add placeholder message
        self.models_placeholder = QLabel(
            '🔄 กดปุ่ม "Refresh Models" เพื่อโหลดรายการโมเดล\n\n'
            '💡 หรือรอให้ระบบโหลดอัตโนมัติหลัง Login'
        )
        self.models_placeholder.setStyleSheet('''
            color: #888;
            font-size: 14px;
            padding: 40px;
        ''')
        self.models_placeholder.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.models_layout.addWidget(self.models_placeholder)

        scroll_area.setWidget(self.models_container)
        layout.addWidget(scroll_area)

        # Download progress section
        progress_group = QGroupBox('📥 Download Progress')
        progress_layout = QVBoxLayout()

        self.model_download_progress = QProgressBar()
        self.model_download_progress.setRange(0, 100)
        self.model_download_progress.setValue(0)
        self.model_download_progress.setStyleSheet('''
            QProgressBar {
                border: 2px solid #4a4a6a;
                border-radius: 10px;
                background-color: rgba(15, 52, 96, 0.8);
                text-align: center;
                height: 30px;
                font-weight: bold;
            }
            QProgressBar::chunk {
                background: qlineargradient(x1:0, y1:0, x2:1, y2:0, stop:0 #22c55e, stop:1 #3b82f6);
                border-radius: 8px;
            }
        ''')
        progress_layout.addWidget(self.model_download_progress)

        self.model_download_status = QLabel('Ready to download')
        self.model_download_status.setStyleSheet('color: #888; font-size: 12px;')
        self.model_download_status.setAlignment(Qt.AlignmentFlag.AlignCenter)
        progress_layout.addWidget(self.model_download_status)

        progress_group.setLayout(progress_layout)
        layout.addWidget(progress_group)

        # Store model widgets for updates
        self.model_cards = {}
        self.model_toggles = {}
        self.model_enabled = {}  # Track which models are enabled for work

        return tab

    def create_model_card(self, model: Dict, is_installed: bool) -> QFrame:
        """Create a sci-fi styled model card with toggle switch"""
        model_id = model.get('model_id', model.get('name', 'unknown'))

        card = QFrame()
        card.setStyleSheet('''
            QFrame {
                background: qlineargradient(x1:0, y1:0, x2:1, y2:1,
                    stop:0 rgba(22, 33, 62, 0.9), stop:1 rgba(15, 52, 96, 0.9));
                border: 2px solid #4a4a6a;
                border-radius: 15px;
                padding: 5px;
            }
            QFrame:hover {
                border-color: #7c3aed;
            }
        ''')
        card_layout = QHBoxLayout(card)
        card_layout.setContentsMargins(15, 12, 15, 12)

        # Left section - Model info
        info_layout = QVBoxLayout()

        # Model name with icon
        name_layout = QHBoxLayout()
        icon_label = QLabel('🤖' if is_installed else '📦')
        icon_label.setStyleSheet('font-size: 24px;')
        name_layout.addWidget(icon_label)

        name_label = QLabel(model.get('name', model_id))
        name_label.setStyleSheet('font-size: 14px; font-weight: bold; color: #fff;')
        name_layout.addWidget(name_label)
        name_layout.addStretch()
        info_layout.addLayout(name_layout)

        # Model details
        vram_gb = model.get('vram_required_mb', 0) / 1024
        size_gb = model.get('size_mb', 0) / 1024
        category = model.get('category', 'unknown')

        details_label = QLabel(f'💾 {size_gb:.1f}GB  |  🎮 VRAM: {vram_gb:.1f}GB  |  📂 {category}')
        details_label.setStyleSheet('font-size: 11px; color: #888;')
        info_layout.addWidget(details_label)

        card_layout.addLayout(info_layout, stretch=1)

        # Right section - Toggle/Download button
        control_layout = QVBoxLayout()
        control_layout.setAlignment(Qt.AlignmentFlag.AlignCenter)

        if is_installed:
            # Sci-fi toggle switch for installed models
            toggle_container = QFrame()
            toggle_container.setStyleSheet('''
                QFrame {
                    background: transparent;
                    border: none;
                }
            ''')
            toggle_layout = QVBoxLayout(toggle_container)
            toggle_layout.setContentsMargins(0, 0, 0, 0)
            toggle_layout.setSpacing(3)

            # Toggle button (styled like spaceship switch)
            toggle_btn = QPushButton()
            toggle_btn.setCheckable(True)
            toggle_btn.setChecked(self.model_enabled.get(model_id, True))
            toggle_btn.setFixedSize(70, 36)
            toggle_btn.setStyleSheet('''
                QPushButton {
                    background: qlineargradient(x1:0, y1:0, x2:1, y2:0,
                        stop:0 #1a1a2e, stop:0.5 #16213e, stop:1 #0f3460);
                    border: 3px solid #dc2626;
                    border-radius: 18px;
                    color: #dc2626;
                    font-weight: bold;
                    font-size: 10px;
                }
                QPushButton:checked {
                    background: qlineargradient(x1:0, y1:0, x2:1, y2:0,
                        stop:0 #064e3b, stop:0.5 #065f46, stop:1 #047857);
                    border: 3px solid #22c55e;
                    color: #22c55e;
                }
                QPushButton:hover {
                    border-width: 4px;
                }
            ''')
            toggle_btn.setText('OFF' if not toggle_btn.isChecked() else 'ON')
            toggle_btn.clicked.connect(lambda checked, mid=model_id, btn=toggle_btn: self.toggle_model(mid, checked, btn))
            toggle_layout.addWidget(toggle_btn, alignment=Qt.AlignmentFlag.AlignCenter)

            # Status indicator lights
            lights_layout = QHBoxLayout()
            lights_layout.setSpacing(4)
            for i in range(3):
                light = QLabel('●')
                if toggle_btn.isChecked():
                    light.setStyleSheet(f'color: #22c55e; font-size: 8px;')
                else:
                    light.setStyleSheet('color: #4a4a4a; font-size: 8px;')
                lights_layout.addWidget(light)
            toggle_layout.addLayout(lights_layout)

            # Store toggle reference
            self.model_toggles[model_id] = toggle_btn

            control_layout.addWidget(toggle_container)

            # Delete button (small)
            delete_btn = QPushButton('🗑️')
            delete_btn.setFixedSize(30, 30)
            delete_btn.setStyleSheet('''
                QPushButton {
                    background: rgba(220, 38, 38, 0.3);
                    border: 1px solid #dc2626;
                    border-radius: 5px;
                    font-size: 12px;
                }
                QPushButton:hover {
                    background: rgba(220, 38, 38, 0.6);
                }
            ''')
            delete_btn.clicked.connect(lambda _, mid=model_id: self.delete_model_by_id(mid))
            control_layout.addWidget(delete_btn, alignment=Qt.AlignmentFlag.AlignCenter)

        else:
            # Download button for non-installed models
            download_btn = QPushButton('⬇️ DOWNLOAD')
            download_btn.setFixedSize(100, 40)
            download_btn.setStyleSheet('''
                QPushButton {
                    background: qlineargradient(x1:0, y1:0, x2:0, y2:1,
                        stop:0 #3b82f6, stop:1 #1d4ed8);
                    border: 2px solid #60a5fa;
                    border-radius: 10px;
                    color: white;
                    font-weight: bold;
                    font-size: 10px;
                }
                QPushButton:hover {
                    background: qlineargradient(x1:0, y1:0, x2:0, y2:1,
                        stop:0 #60a5fa, stop:1 #3b82f6);
                    border-color: #93c5fd;
                }
                QPushButton:pressed {
                    background: #1e40af;
                }
            ''')
            download_btn.clicked.connect(lambda _, m=model: self.download_model_from_card(m))
            control_layout.addWidget(download_btn)

        card_layout.addLayout(control_layout)

        return card

    def toggle_model(self, model_id: str, enabled: bool, btn: QPushButton):
        """Toggle model enabled/disabled for work"""
        self.model_enabled[model_id] = enabled
        btn.setText('ON' if enabled else 'OFF')

        # Update indicator lights
        toggle_container = btn.parent()
        if toggle_container:
            lights = toggle_container.findChildren(QLabel)
            for light in lights:
                if light.text() == '●':
                    light.setStyleSheet(f'color: {"#22c55e" if enabled else "#4a4a4a"}; font-size: 8px;')

        # Notify server about model status
        if self.node_id:
            try:
                self.api.update_model_status(self.node_id, model_id, enabled)
                self.append_log(f'Model {model_id}: {"ENABLED" if enabled else "DISABLED"}')
            except Exception as e:
                self.append_log(f'Warning: Failed to update model status: {e}')

        self.update_active_count()

    def update_active_count(self):
        """Update the active models count"""
        active = sum(1 for v in self.model_enabled.values() if v)
        self.models_active_label.setText(f'{active} active')

    def download_model_from_card(self, model: Dict):
        """Download model from card button click"""
        model_id = model['model_id']
        huggingface_id = model['huggingface_id']
        category = model.get('category', 'stable-diffusion').replace('_', '-')

        self.append_log(f'Starting download: {model_id}')
        self.model_download_status.setText(f'⏳ Downloading {model_id}...')
        self.model_download_progress.setValue(0)

        success = self.model_manager.download_model(
            model_id=model_id,
            huggingface_id=huggingface_id,
            category=category,
        )

        if not success:
            self.show_error('Failed to start download')

    def delete_model_by_id(self, model_id: str):
        """Delete model by ID"""
        reply = QMessageBox.question(
            self,
            'Delete Model',
            f'⚠️ Are you sure you want to delete {model_id}?',
            QMessageBox.StandardButton.Yes | QMessageBox.StandardButton.No
        )

        if reply == QMessageBox.StandardButton.Yes:
            if self.model_manager.delete_model(model_id):
                self.append_log(f'Model {model_id} deleted')
                if model_id in self.model_enabled:
                    del self.model_enabled[model_id]
                if model_id in self.model_toggles:
                    del self.model_toggles[model_id]
                self.refresh_installed_models()
                self.rebuild_model_cards()

                if self.node_id:
                    try:
                        self.api.unregister_model(self.node_id, model_id)
                    except:
                        pass
            else:
                self.show_error(f'Failed to delete {model_id}')

    def rebuild_model_cards(self):
        """Rebuild all model cards"""
        # Clear existing cards
        while self.models_layout.count():
            item = self.models_layout.takeAt(0)
            if item.widget():
                item.widget().deleteLater()

        self.model_cards.clear()

        installed_ids = self.model_manager.get_installed_model_list()

        # Check if we have any models to show
        has_models = len(installed_ids) > 0 or len(self.available_models) > 0

        if not has_models:
            # Show placeholder if no models
            placeholder = QLabel(
                '🔄 กดปุ่ม "Refresh Models" เพื่อโหลดรายการโมเดล\n\n'
                '💡 หรือรอให้ระบบโหลดอัตโนมัติหลัง Login'
            )
            placeholder.setStyleSheet('''
                color: #888;
                font-size: 14px;
                padding: 40px;
            ''')
            placeholder.setAlignment(Qt.AlignmentFlag.AlignCenter)
            self.models_layout.addWidget(placeholder)
            self.models_layout.addStretch()
            return

        # Add installed models first (with toggle switches)
        for model_id in installed_ids:
            model_info = {'model_id': model_id, 'name': model_id, 'category': 'installed', 'size_mb': 0, 'vram_required_mb': 0}
            # Find full model info if available
            for m in self.available_models:
                if m.get('model_id') == model_id:
                    model_info = m
                    break
            card = self.create_model_card(model_info, is_installed=True)
            self.model_cards[model_id] = card
            self.models_layout.addWidget(card)

        # Add available models (with download buttons)
        for model in self.available_models:
            model_id = model.get('model_id')
            if model_id and model_id not in installed_ids:
                card = self.create_model_card(model, is_installed=False)
                self.model_cards[model_id] = card
                self.models_layout.addWidget(card)

        # Add stretch at end
        self.models_layout.addStretch()

        self.update_active_count()

    def refresh_installed_models(self):
        """Refresh the installed models info"""
        summary = self.model_manager.get_models_summary()

        # Set enabled state for all installed models
        for model in summary['models']:
            if model['model_id'] not in self.model_enabled:
                self.model_enabled[model['model_id']] = True  # Default to enabled

        self.models_count_label.setText(f"{summary['total_models']} models")
        self.models_size_label.setText(f"{summary['total_size_mb']:.0f} MB")
        self.update_active_count()

    def refresh_available_models(self):
        """Fetch available models from server"""
        if not self.node_id:
            self.show_error('Please login and register node first')
            return

        self.append_log('Fetching available models...')

        def fetch():
            try:
                result = self.api.get_available_models(self.node_id)
                if result.get('success'):
                    self.available_models = result['data']['models']
                    self.signals.models_updated.emit()
                    self.signals.log_message.emit(f"Found {len(self.available_models)} available models")
            except APIError as e:
                self.signals.error.emit(f'Failed to fetch models: {e.message}')

        threading.Thread(target=fetch, daemon=True).start()

    def update_available_models_list(self):
        """Update the model cards"""
        self.rebuild_model_cards()

    def download_selected_model(self):
        """Legacy method - now handled by download_model_from_card"""
        pass

    def on_model_download_progress(self, model_id: str, status: str, progress: int, error: str):
        """Handle model download progress updates"""
        self.signals.model_download_progress.emit(model_id, status, progress, error or '')

    def handle_model_download_progress(self, model_id: str, status: str, progress: int, error: str):
        """Update UI for download progress"""
        self.model_download_progress.setValue(progress)
        self.model_download_status.setText(f'{status}: {model_id} ({progress}%)')

        if status == 'completed':
            self.model_download_progress.setValue(100)
            self.model_download_status.setText(f'✅ {model_id} installed successfully!')
            self.refresh_installed_models()
            self.rebuild_model_cards()

            # Register with server
            if self.node_id:
                try:
                    self.api.register_model_installed(self.node_id, model_id)
                    self.append_log(f'Model {model_id} registered with server')
                except Exception as e:
                    self.append_log(f'Warning: Failed to register model with server: {e}')

        elif status == 'failed':
            self.model_download_progress.setValue(0)
            self.model_download_status.setText(f'❌ Download failed: {error}')

    def delete_selected_model(self):
        """Legacy method - now handled by delete_model_by_id"""
        pass

    def setup_signals(self):
        """Connect signals to slots"""
        self.signals.log_message.connect(self.append_log)
        self.signals.status_update.connect(self.update_status)
        self.signals.progress_update.connect(self.progress_bar.setValue)
        self.signals.earnings_update.connect(self.update_earnings_display)
        self.signals.error.connect(self.show_error)
        self.signals.connection_status.connect(self.update_connection_status)
        self.signals.model_download_progress.connect(self.handle_model_download_progress)
        self.signals.models_updated.connect(self.update_available_models_list)

    def setup_timers(self):
        """Setup background timers"""
        self.heartbeat_timer = QTimer()
        self.heartbeat_timer.timeout.connect(self.send_heartbeat)

        self.work_timer = QTimer()
        self.work_timer.timeout.connect(self.check_for_work)

        self.sync_timer = QTimer()
        self.sync_timer.timeout.connect(self.check_connection)
        self.sync_timer.start(5000)  # Check every 5 seconds

        self.uptime_timer = QTimer()
        self.uptime_timer.timeout.connect(self.update_uptime)
        self.session_start_time = None

    def load_saved_settings(self):
        """Load saved settings"""
        try:
            if os.path.exists('.settings'):
                with open('.settings', 'r') as f:
                    data = json.load(f)
                    self.gpu_power_limit = data.get('gpu_power', 100)
        except Exception as e:
            logger.error(f"Failed to load settings: {e}")

    def save_settings(self):
        """Save settings"""
        try:
            with open('.settings', 'w') as f:
                json.dump({
                    'gpu_power': self.gpu_power_limit
                }, f)
        except Exception as e:
            logger.error(f"Failed to save settings: {e}")

    def load_saved_token(self):
        """Load saved authentication token"""
        try:
            if os.path.exists('.token'):
                with open('.token', 'r') as f:
                    data = json.load(f)
                    token = data.get('token')
                    if token:
                        self.api.set_token(token)
                        self.verify_token()
        except Exception as e:
            logger.error(f"Failed to load token: {e}")

    def save_token(self, token: str):
        """Save authentication token"""
        try:
            with open('.token', 'w') as f:
                json.dump({'token': token}, f)
        except Exception as e:
            logger.error(f"Failed to save token: {e}")

    def verify_token(self):
        """Verify saved token is still valid"""
        try:
            result = self.api.get_me()
            if result.get('success'):
                self.user_data = result['data']['user']
                self.on_login_success()
        except APIError:
            pass

    def on_login(self):
        """Handle login button click"""
        email = self.email_input.text().strip()
        password = self.password_input.text()

        if not email or not password:
            self.show_error('Please enter email and password')
            return

        try:
            result = self.api.login(email, password)
            if result.get('success'):
                self.save_token(result['data']['token'])
                self.user_data = result['data']['user']
                self.on_login_success()
        except APIError as e:
            self.show_error(f'Login failed: {e.message}')

    def on_register(self):
        """Handle register button click"""
        from PyQt6.QtWidgets import QDialog, QDialogButtonBox

        dialog = QDialog(self)
        dialog.setWindowTitle('Register New Account')
        dialog.setStyleSheet(self.styleSheet())
        dialog.setMinimumWidth(400)

        layout = QFormLayout()

        name_input = QLineEdit()
        name_input.setPlaceholderText('Your name')
        layout.addRow('Name:', name_input)

        email_input = QLineEdit()
        email_input.setPlaceholderText('your@email.com')
        layout.addRow('Email:', email_input)

        password_input = QLineEdit()
        password_input.setEchoMode(QLineEdit.EchoMode.Password)
        password_input.setPlaceholderText('Minimum 8 characters')
        layout.addRow('Password:', password_input)

        referral_input = QLineEdit()
        referral_input.setPlaceholderText('Optional referral code')
        layout.addRow('Referral Code:', referral_input)

        buttons = QDialogButtonBox(
            QDialogButtonBox.StandardButton.Ok | QDialogButtonBox.StandardButton.Cancel
        )
        buttons.accepted.connect(dialog.accept)
        buttons.rejected.connect(dialog.reject)
        layout.addRow(buttons)

        dialog.setLayout(layout)

        if dialog.exec():
            try:
                result = self.api.register(
                    name_input.text().strip(),
                    email_input.text().strip(),
                    password_input.text(),
                    referral_input.text().strip() or None
                )
                if result.get('success'):
                    self.save_token(result['data']['token'])
                    self.user_data = result['data']['user']
                    self.on_login_success()
            except APIError as e:
                self.show_error(f'Registration failed: {e.message}')

    def on_login_success(self):
        """Handle successful login"""
        self.stack.setCurrentWidget(self.main_page)
        self.user_label.setText(f"Welcome, {self.user_data['name']}")
        self.append_log('Logged in successfully')
        self.register_node()
        self.check_connection()
        # Load installed models
        self.refresh_installed_models()

    def on_logout(self):
        """Handle logout"""
        self.stop_mining()
        self.api.token = None
        self.node_id = None
        self.user_data = None

        if os.path.exists('.token'):
            os.remove('.token')

        self.stack.setCurrentWidget(self.login_page)
        self.password_input.clear()
        self.update_connection_status(False)

    def register_node(self):
        """Register this machine as a GPU node"""
        try:
            reg_data = self.hardware.get_registration_data()
            result = self.api.register_node(reg_data)

            if result.get('success'):
                self.node_id = result['data']['node_id']
                self.node_id_label.setText(self.node_id)
                self.append_log(f'Node registered: {self.node_id}')

                if result['data'].get('benchmark_required'):
                    self.append_log('Benchmark required - please run benchmark')

                # Auto-load available models after node registration
                self.refresh_available_models()

        except APIError as e:
            self.show_error(f'Node registration failed: {e.message}')

    def on_power_changed(self, value):
        """Handle GPU power slider change"""
        self.gpu_power_limit = value
        self.power_label.setText(f'{value}%')
        self.worker.set_power_limit(value)
        self.save_settings()

    def set_power(self, value):
        """Set power to preset value"""
        self.power_slider.setValue(value)

    def check_connection(self):
        """Check connection to server"""
        try:
            result = self.api.get_pool_stats()
            self.signals.connection_status.emit(True)
            self.sync_status_label.setText('Connected')
            self.sync_status_label.setStyleSheet('color: #22c55e;')
            self.last_sync_label.setText(time.strftime('%H:%M:%S'))
        except:
            self.signals.connection_status.emit(False)
            self.sync_status_label.setText('Disconnected')
            self.sync_status_label.setStyleSheet('color: #dc2626;')

    def update_connection_status(self, connected: bool):
        """Update connection indicator"""
        self.is_connected = connected
        if connected:
            self.connection_indicator.setText('● Connected')
            self.connection_indicator.setStyleSheet('color: #22c55e; font-weight: bold;')
        else:
            self.connection_indicator.setText('● Disconnected')
            self.connection_indicator.setStyleSheet('color: #dc2626; font-weight: bold;')

    def manual_sync(self):
        """Manual sync button handler"""
        self.append_log('Manual sync...')
        self.check_connection()
        self.refresh_earnings()

    def on_start(self):
        """Start mining/working"""
        if not self.node_id:
            self.show_error('Node not registered')
            return

        self.is_running = True
        self.session_start_time = time.time()
        self.start_btn.setEnabled(False)
        self.stop_btn.setEnabled(True)
        self.update_status('Online')

        # Start timers
        self.heartbeat_timer.start(HEARTBEAT_INTERVAL * 1000)
        self.work_timer.start(WORK_POLL_INTERVAL * 1000)
        self.uptime_timer.start(1000)

        self.append_log(f'Started mining at {self.gpu_power_limit}% power')
        self.send_heartbeat()

    def on_stop(self):
        """Stop mining/working"""
        self.stop_mining()
        self.append_log('Stopped mining')

    def stop_mining(self):
        """Stop all mining operations"""
        self.is_running = False
        self.session_start_time = None
        self.start_btn.setEnabled(True)
        self.stop_btn.setEnabled(False)
        self.update_status('Offline')

        self.heartbeat_timer.stop()
        self.work_timer.stop()
        self.uptime_timer.stop()

        if self.node_id:
            try:
                self.api.disconnect_node(self.node_id)
            except Exception:
                pass

    def update_uptime(self):
        """Update uptime display"""
        if self.session_start_time:
            elapsed = int(time.time() - self.session_start_time)
            hours = elapsed // 3600
            minutes = (elapsed % 3600) // 60
            seconds = elapsed % 60
            self.uptime_label.setText(f'{hours}:{minutes:02d}:{seconds:02d}')

    def on_benchmark(self):
        """Run GPU benchmark"""
        if not self.node_id:
            self.show_error('Node not registered')
            return

        self.append_log('Starting benchmark...')
        self.progress_bar.setValue(0)

        def run_benchmark():
            self.worker.set_progress_callback(
                lambda p: self.signals.progress_update.emit(p)
            )
            result = self.worker.run_benchmark()

            try:
                api_result = self.api.submit_benchmark(
                    self.node_id,
                    result['score'],
                    result
                )
                # Update rank based on score
                rank_info = self.calculate_rank(result['score'])
                self.rank_label.setText(f"{rank_info['title']} ({rank_info['stars']}⭐)")
                self.signals.log_message.emit(
                    f"Benchmark complete: score={result['score']}, rank={rank_info['title']}"
                )
            except APIError as e:
                self.signals.error.emit(f'Failed to submit benchmark: {e.message}')

        threading.Thread(target=run_benchmark, daemon=True).start()

    def calculate_rank(self, score: int) -> Dict:
        """Calculate rank based on benchmark score"""
        if score >= 50000:
            return {'rank': 'diamond', 'stars': 5, 'title': 'Diamond'}
        elif score >= 30000:
            return {'rank': 'platinum', 'stars': 5, 'title': 'Platinum'}
        elif score >= 20000:
            return {'rank': 'gold', 'stars': 4, 'title': 'Gold'}
        elif score >= 10000:
            return {'rank': 'silver', 'stars': 3, 'title': 'Silver'}
        elif score >= 5000:
            return {'rank': 'bronze', 'stars': 2, 'title': 'Bronze'}
        else:
            return {'rank': 'bronze', 'stars': 1, 'title': 'Beginner'}

    def send_heartbeat(self):
        """Send heartbeat to server"""
        if not self.node_id or not self.is_running:
            return

        try:
            metrics = self.hardware.get_gpu_metrics()
            status = 'working' if self.worker.is_working else 'idle'

            result = self.api.heartbeat(self.node_id, status, {
                'gpu_temp': metrics.get('temperature'),
                'gpu_usage': metrics.get('gpu_usage'),
                'memory_usage': metrics.get('memory_usage'),
                'power_limit': self.gpu_power_limit,
            })

            self.signals.connection_status.emit(True)

            # Check for verification task
            if result.get('data', {}).get('verification_task'):
                task = result['data']['verification_task']
                self.process_verification(task)

        except APIError as e:
            self.append_log(f'Heartbeat failed: {e.message}')
            self.signals.connection_status.emit(False)

    def check_for_work(self):
        """Check for available work"""
        if not self.node_id or not self.is_running or self.worker.is_working:
            return

        try:
            result = self.api.get_work(self.node_id)

            if result.get('data', {}).get('has_work'):
                chunk = result['data']['chunk']
                self.process_work(chunk)

        except APIError as e:
            self.append_log(f'Work check failed: {e.message}')

    def process_work(self, chunk: Dict):
        """Process a work chunk"""
        chunk_id = chunk['chunk_id']
        self.append_log(f'Received work: {chunk_id}')
        self.work_info_label.setText(f'Processing: {chunk["job_title"]}')
        self.update_status('Working')

        def do_work():
            try:
                self.api.start_work(self.node_id, chunk_id)

                self.worker.set_progress_callback(
                    lambda p: self.signals.progress_update.emit(p)
                )
                self.worker.set_power_limit(self.gpu_power_limit)

                # Pass complete chunk data including generation parameters
                result = self.worker.process_job({
                    'chunk_id': chunk_id,
                    'job_type': chunk.get('job_type', 'image'),
                    'generation': chunk.get('generation', {}),  # Pass generation params
                    'params': chunk.get('params', {}),
                    'job_params': chunk.get('job_params', {}),
                })

                if result['success']:
                    # If we have a generated file, upload it first
                    partial_result_url = None
                    if result.get('result_file') and os.path.exists(result['result_file']):
                        try:
                            upload_result = self.api.upload_partial_result(
                                self.node_id,
                                chunk_id,
                                result['result_file']
                            )
                            if upload_result.get('success'):
                                partial_result_url = upload_result['data']['url']
                                self.signals.log_message.emit(f'Uploaded result: {partial_result_url}')
                        except Exception as e:
                            self.signals.log_message.emit(f'Upload warning: {e}')

                    self.api.submit_work(
                        self.node_id,
                        chunk_id,
                        result['result_hash'],
                        result_file=result.get('result_file'),
                        partial_result_url=partial_result_url,
                        metadata=result.get('metadata')
                    )
                    self.signals.log_message.emit(f'Work completed: {chunk_id}')
                    # Update session stats
                    current = int(self.jobs_completed_label.text())
                    self.jobs_completed_label.setText(str(current + 1))
                    self.refresh_earnings()
                else:
                    self.api.report_error(self.node_id, chunk_id, result['error'])
                    self.signals.log_message.emit(f'Work failed: {result["error"]}')

            except APIError as e:
                self.signals.error.emit(f'Work submission failed: {e.message}')
            finally:
                self.signals.status_update.emit('Idle' if self.is_running else 'Offline')
                self.work_info_label.setText('No active work')

        threading.Thread(target=do_work, daemon=True).start()

    def process_verification(self, task: Dict):
        """Process verification task"""
        self.append_log(f'Verification task received: {task["type"]}')

        def do_verify():
            result = self.worker.process_verification(task)

            try:
                self.api.submit_verification(
                    self.node_id,
                    task['id'],
                    result['result_hash'],
                    result['time_taken']
                )
                self.signals.log_message.emit('Verification completed')
            except APIError as e:
                self.signals.error.emit(f'Verification failed: {e.message}')

        threading.Thread(target=do_verify, daemon=True).start()

    def refresh_earnings(self):
        """Refresh earnings display"""
        try:
            result = self.api.get_earnings_summary()
            if result.get('success'):
                self.signals.earnings_update.emit(result['data'])
        except APIError as e:
            self.append_log(f'Failed to refresh earnings: {e.message}')

    def update_earnings_display(self, data: Dict):
        """Update earnings labels"""
        self.balance_label.setText(f"${data.get('balance', 0):.2f}")
        self.pending_label.setText(f"${data.get('pending_earnings', 0):.2f}")
        self.total_earned_label.setText(f"${data.get('total_earned', 0):.2f}")

    def update_status(self, status: str):
        """Update status display"""
        colors = {
            'Online': '#22c55e',
            'Working': '#3b82f6',
            'Idle': '#eab308',
            'Offline': '#888',
        }
        self.status_label.setText(status)
        self.status_label.setStyleSheet(f'color: {colors.get(status, "#888")}; font-weight: bold;')

    def append_log(self, message: str):
        """Append message to log"""
        timestamp = time.strftime('%H:%M:%S')
        self.log_text.append(f'[{timestamp}] {message}')
        logger.info(message)

    def show_error(self, message: str):
        """Show error message"""
        QMessageBox.warning(self, 'Error', message)
        self.append_log(f'ERROR: {message}')

    def save_logs(self):
        """Save logs to file"""
        filename = f'gpu_client_logs_{time.strftime("%Y%m%d_%H%M%S")}.txt'
        with open(filename, 'w') as f:
            f.write(self.log_text.toPlainText())
        self.append_log(f'Logs saved to {filename}')

    def closeEvent(self, event):
        """Handle window close"""
        self.stop_mining()
        self.save_settings()
        event.accept()


def main():
    app = QApplication(sys.argv)
    app.setStyle('Fusion')

    window = GPUClientApp()
    window.show()

    sys.exit(app.exec())


if __name__ == '__main__':
    main()
