"""
Model Manager - Download and manage AI models from HuggingFace
"""
import os
import sys
import json
import hashlib
import logging
import threading
import time
from pathlib import Path
from typing import Dict, List, Optional, Callable
from dataclasses import dataclass

logger = logging.getLogger(__name__)

# Try to import huggingface_hub
try:
    from huggingface_hub import snapshot_download, hf_hub_download
    HF_HUB_AVAILABLE = True
except ImportError:
    HF_HUB_AVAILABLE = False
    logger.warning("huggingface_hub not installed - model downloads disabled")


@dataclass
class DownloadProgress:
    model_id: str
    status: str  # 'pending', 'downloading', 'completed', 'failed'
    progress: int  # 0-100
    downloaded_mb: float
    total_mb: float
    speed_mbps: float
    eta_seconds: int
    error: Optional[str] = None


class ModelManager:
    """Manages AI model downloads and storage"""

    def __init__(self, models_dir: Optional[Path] = None):
        self.models_dir = models_dir or Path(os.environ.get(
            'GPU_SHARE_MODELS',
            Path.home() / '.gpu_share' / 'models'
        ))
        self.models_dir.mkdir(parents=True, exist_ok=True)

        # Track downloads
        self.active_downloads: Dict[str, DownloadProgress] = {}
        self.installed_models: Dict[str, dict] = {}
        self.progress_callback: Optional[Callable] = None

        # Load installed models
        self._load_installed_models()

    def set_progress_callback(self, callback: Callable):
        """Set callback for download progress updates"""
        self.progress_callback = callback

    def _load_installed_models(self):
        """Load info about installed models"""
        models_json = self.models_dir / 'installed_models.json'
        if models_json.exists():
            try:
                with open(models_json, 'r') as f:
                    self.installed_models = json.load(f)
            except Exception as e:
                logger.error(f"Failed to load installed models: {e}")
                self.installed_models = {}

    def _save_installed_models(self):
        """Save installed models info"""
        models_json = self.models_dir / 'installed_models.json'
        try:
            with open(models_json, 'w') as f:
                json.dump(self.installed_models, f, indent=2)
        except Exception as e:
            logger.error(f"Failed to save installed models: {e}")

    def get_install_path(self, model_id: str, category: str = 'stable-diffusion') -> Path:
        """Get the installation path for a model"""
        return self.models_dir / category / model_id

    def is_model_installed(self, model_id: str) -> bool:
        """Check if a model is installed"""
        return model_id in self.installed_models

    def get_installed_model_list(self) -> List[str]:
        """Get list of installed model IDs"""
        return list(self.installed_models.keys())

    def get_model_info(self, model_id: str) -> Optional[dict]:
        """Get info about an installed model"""
        return self.installed_models.get(model_id)

    def download_model(
        self,
        model_id: str,
        huggingface_id: str,
        category: str = 'stable-diffusion',
        hf_token: Optional[str] = None,
    ) -> bool:
        """
        Download a model from HuggingFace

        Args:
            model_id: Local model identifier
            huggingface_id: HuggingFace model ID (e.g., "stabilityai/stable-diffusion-xl-base-1.0")
            category: Model category for storage organization
            hf_token: Optional HuggingFace token for private models

        Returns:
            True if download succeeded
        """
        if not HF_HUB_AVAILABLE:
            logger.error("huggingface_hub not installed")
            self._update_progress(model_id, 'failed', 0, error="huggingface_hub not installed")
            return False

        if model_id in self.active_downloads:
            logger.warning(f"Model {model_id} is already downloading")
            return False

        # Initialize progress
        self.active_downloads[model_id] = DownloadProgress(
            model_id=model_id,
            status='pending',
            progress=0,
            downloaded_mb=0,
            total_mb=0,
            speed_mbps=0,
            eta_seconds=0,
        )

        def do_download():
            try:
                self._update_progress(model_id, 'downloading', 5)

                install_path = self.get_install_path(model_id, category)
                install_path.mkdir(parents=True, exist_ok=True)

                logger.info(f"Downloading {huggingface_id} to {install_path}")

                # Download using huggingface_hub
                self._update_progress(model_id, 'downloading', 10)

                # Use snapshot_download for complete model
                snapshot_download(
                    huggingface_id,
                    local_dir=str(install_path),
                    local_dir_use_symlinks=False,
                    token=hf_token,
                    ignore_patterns=[
                        "*.md",
                        "*.txt",
                        ".git*",
                        "*.msgpack",
                        "*.h5",
                        "*.ot",
                        "flax_model.msgpack",
                        "pytorch_model.bin",  # Prefer safetensors
                    ],
                )

                self._update_progress(model_id, 'downloading', 90)

                # Verify download
                if not self._verify_model(install_path, category):
                    raise Exception("Model verification failed - missing required files")

                # Calculate total size
                total_size = sum(
                    f.stat().st_size for f in install_path.rglob('*') if f.is_file()
                )
                size_mb = total_size / (1024 * 1024)

                # Save model info
                self.installed_models[model_id] = {
                    'model_id': model_id,
                    'huggingface_id': huggingface_id,
                    'category': category,
                    'path': str(install_path),
                    'size_mb': round(size_mb, 2),
                    'installed_at': time.strftime('%Y-%m-%d %H:%M:%S'),
                }
                self._save_installed_models()

                self._update_progress(model_id, 'completed', 100)
                logger.info(f"Model {model_id} downloaded successfully ({size_mb:.0f} MB)")

            except Exception as e:
                logger.error(f"Download failed for {model_id}: {e}")
                self._update_progress(model_id, 'failed', 0, error=str(e))
            finally:
                # Clean up active download
                if model_id in self.active_downloads:
                    if self.active_downloads[model_id].status != 'completed':
                        del self.active_downloads[model_id]

        # Start download in background thread
        thread = threading.Thread(target=do_download, daemon=True)
        thread.start()

        return True

    def _verify_model(self, path: Path, category: str) -> bool:
        """Verify model files are present"""
        # Check for common model files based on category
        if category == 'stable-diffusion':
            required = ['model_index.json']
        elif category == 'flux':
            required = []  # Flux has different structure
        else:
            required = []

        # Check required files
        for filename in required:
            if not (path / filename).exists():
                logger.warning(f"Missing required file: {filename}")
                return False

        # Check for any model files
        model_extensions = ['.safetensors', '.ckpt', '.bin', '.pt']
        has_model_file = any(
            f.suffix in model_extensions
            for f in path.rglob('*') if f.is_file()
        )

        if not has_model_file:
            logger.warning("No model files found")
            return False

        return True

    def _update_progress(
        self,
        model_id: str,
        status: str,
        progress: int,
        error: Optional[str] = None
    ):
        """Update download progress"""
        if model_id in self.active_downloads:
            self.active_downloads[model_id].status = status
            self.active_downloads[model_id].progress = progress
            self.active_downloads[model_id].error = error

        if self.progress_callback:
            self.progress_callback(model_id, status, progress, error)

    def get_download_progress(self, model_id: str) -> Optional[DownloadProgress]:
        """Get current download progress for a model"""
        return self.active_downloads.get(model_id)

    def cancel_download(self, model_id: str) -> bool:
        """Cancel an active download"""
        if model_id in self.active_downloads:
            self._update_progress(model_id, 'cancelled', 0)
            del self.active_downloads[model_id]
            return True
        return False

    def delete_model(self, model_id: str) -> bool:
        """Delete an installed model"""
        if model_id not in self.installed_models:
            return False

        try:
            import shutil
            model_info = self.installed_models[model_id]
            model_path = Path(model_info['path'])

            if model_path.exists():
                shutil.rmtree(model_path)

            del self.installed_models[model_id]
            self._save_installed_models()

            logger.info(f"Model {model_id} deleted")
            return True

        except Exception as e:
            logger.error(f"Failed to delete model {model_id}: {e}")
            return False

    def get_total_size_mb(self) -> float:
        """Get total size of all installed models"""
        return sum(m.get('size_mb', 0) for m in self.installed_models.values())

    def get_models_summary(self) -> Dict:
        """Get summary of installed models"""
        return {
            'total_models': len(self.installed_models),
            'total_size_mb': self.get_total_size_mb(),
            'models_dir': str(self.models_dir),
            'models': [
                {
                    'model_id': m['model_id'],
                    'category': m.get('category', 'unknown'),
                    'size_mb': m.get('size_mb', 0),
                    'installed_at': m.get('installed_at', 'unknown'),
                }
                for m in self.installed_models.values()
            ],
        }


def install_huggingface_hub():
    """Install huggingface_hub package"""
    import subprocess
    try:
        subprocess.run(
            [sys.executable, '-m', 'pip', 'install', 'huggingface_hub'],
            check=True
        )
        print("huggingface_hub installed successfully")
        return True
    except Exception as e:
        print(f"Failed to install huggingface_hub: {e}")
        return False


if __name__ == '__main__':
    import argparse

    parser = argparse.ArgumentParser(description='Model Manager')
    parser.add_argument('--install-deps', action='store_true', help='Install dependencies')
    parser.add_argument('--list', action='store_true', help='List installed models')
    parser.add_argument('--download', type=str, help='Download a model (HuggingFace ID)')
    parser.add_argument('--model-id', type=str, help='Local model ID')
    parser.add_argument('--category', type=str, default='stable-diffusion', help='Model category')
    parser.add_argument('--delete', type=str, help='Delete a model')

    args = parser.parse_args()

    logging.basicConfig(level=logging.INFO)

    if args.install_deps:
        install_huggingface_hub()
        sys.exit(0)

    manager = ModelManager()

    if args.list:
        summary = manager.get_models_summary()
        print(json.dumps(summary, indent=2))
        sys.exit(0)

    if args.download:
        model_id = args.model_id or args.download.split('/')[-1]

        def progress_cb(mid, status, progress, error):
            print(f"[{progress}%] {status}" + (f" - {error}" if error else ""))

        manager.set_progress_callback(progress_cb)

        print(f"Downloading {args.download} as {model_id}...")
        manager.download_model(model_id, args.download, args.category)

        # Wait for download
        while model_id in manager.active_downloads:
            time.sleep(1)

        sys.exit(0)

    if args.delete:
        if manager.delete_model(args.delete):
            print(f"Model {args.delete} deleted")
        else:
            print(f"Failed to delete {args.delete}")
        sys.exit(0)

    # Default: show summary
    summary = manager.get_models_summary()
    print(json.dumps(summary, indent=2))
