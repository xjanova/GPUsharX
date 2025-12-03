"""
AI Generation Engine - Actual AI model inference
Supports: Stable Diffusion, FLUX, AnimateDiff
"""
import os
import sys
import json
import time
import logging
import hashlib
from pathlib import Path
from typing import Dict, Optional, Callable, List
from dataclasses import dataclass

logger = logging.getLogger(__name__)

# Model paths
MODELS_DIR = Path(os.environ.get('GPU_SHARE_MODELS', Path.home() / '.gpu_share' / 'models'))

@dataclass
class GenerationResult:
    success: bool
    output_path: Optional[str] = None
    result_hash: Optional[str] = None
    error: Optional[str] = None
    processing_time_ms: int = 0
    metadata: Optional[Dict] = None


class AIEngine:
    """AI Generation Engine for image/video generation"""

    def __init__(self, models_dir: Optional[Path] = None):
        self.models_dir = models_dir or MODELS_DIR
        self.models_dir.mkdir(parents=True, exist_ok=True)

        self.device = None
        self.current_model = None
        self.current_model_id = None
        self.pipeline = None
        self.progress_callback: Optional[Callable] = None

        self._check_dependencies()

    def _check_dependencies(self):
        """Check if required packages are installed"""
        self.has_torch = False
        self.has_diffusers = False
        self.has_transformers = False

        try:
            import torch
            self.has_torch = True
            self.device = "cuda" if torch.cuda.is_available() else "cpu"
            logger.info(f"PyTorch available, device: {self.device}")
        except ImportError:
            logger.warning("PyTorch not installed")

        try:
            import diffusers
            self.has_diffusers = True
            logger.info("Diffusers available")
        except ImportError:
            logger.warning("Diffusers not installed")

        try:
            import transformers
            self.has_transformers = True
            logger.info("Transformers available")
        except ImportError:
            logger.warning("Transformers not installed")

    def set_progress_callback(self, callback: Callable):
        """Set callback for progress updates"""
        self.progress_callback = callback

    def _update_progress(self, progress: int, status: str = ""):
        """Update progress"""
        if self.progress_callback:
            self.progress_callback(progress, status)

    def is_ready(self) -> bool:
        """Check if engine is ready for generation"""
        return self.has_torch and self.has_diffusers and self.device == "cuda"

    def get_status(self) -> Dict:
        """Get engine status"""
        return {
            'ready': self.is_ready(),
            'device': self.device,
            'has_torch': self.has_torch,
            'has_diffusers': self.has_diffusers,
            'has_transformers': self.has_transformers,
            'current_model': self.current_model_id,
            'models_dir': str(self.models_dir),
        }

    def list_installed_models(self) -> List[str]:
        """List installed models"""
        models = []
        for category in ['stable-diffusion', 'flux', 'animatediff']:
            category_dir = self.models_dir / category
            if category_dir.exists():
                for model_dir in category_dir.iterdir():
                    if model_dir.is_dir():
                        models.append(f"{category}/{model_dir.name}")
        return models

    def is_model_installed(self, model_id: str, huggingface_id: str) -> bool:
        """Check if a model is installed locally"""
        # Check local path
        for category in ['stable-diffusion', 'flux', 'animatediff', 'other']:
            model_path = self.models_dir / category / model_id
            if model_path.exists() and self._verify_model(model_path):
                return True

        # Check HuggingFace cache
        try:
            from huggingface_hub import try_to_load_from_cache
            # Check if model is in cache
            cached = try_to_load_from_cache(huggingface_id, "model_index.json")
            if cached:
                return True
        except:
            pass

        return False

    def _verify_model(self, model_path: Path) -> bool:
        """Verify model files exist"""
        # Check for common model files
        required_files = ['model_index.json']
        for f in required_files:
            if (model_path / f).exists():
                return True

        # Check for single file models
        for ext in ['.safetensors', '.ckpt', '.bin', '.pt']:
            for f in model_path.glob(f'*{ext}'):
                return True

        return False

    def load_model(self, model_id: str, huggingface_id: str, model_type: str = 'stable_diffusion') -> bool:
        """Load an AI model"""
        if not self.is_ready():
            logger.error("Engine not ready - missing dependencies")
            return False

        if self.current_model_id == model_id and self.pipeline is not None:
            logger.info(f"Model {model_id} already loaded")
            return True

        self._update_progress(10, f"Loading model: {model_id}")

        try:
            import torch
            from diffusers import (
                DiffusionPipeline,
                StableDiffusionPipeline,
                StableDiffusionXLPipeline,
                FluxPipeline,
            )

            # Unload previous model
            if self.pipeline is not None:
                del self.pipeline
                torch.cuda.empty_cache()

            self._update_progress(20, "Initializing pipeline")

            # Determine pipeline type
            pipeline_class = DiffusionPipeline
            kwargs = {
                'torch_dtype': torch.float16,
                'use_safetensors': True,
            }

            if model_type == 'flux':
                pipeline_class = FluxPipeline
            elif 'xl' in model_id.lower() or 'sdxl' in model_id.lower():
                pipeline_class = StableDiffusionXLPipeline
            elif model_type == 'stable_diffusion':
                pipeline_class = StableDiffusionPipeline

            self._update_progress(30, "Downloading/loading model files")

            # Try local path first
            local_path = None
            for category in ['stable-diffusion', 'flux', 'animatediff', 'other']:
                path = self.models_dir / category / model_id
                if path.exists() and self._verify_model(path):
                    local_path = path
                    break

            if local_path:
                self.pipeline = pipeline_class.from_pretrained(
                    str(local_path),
                    **kwargs
                )
            else:
                # Download from HuggingFace
                self.pipeline = pipeline_class.from_pretrained(
                    huggingface_id,
                    **kwargs
                )

            self._update_progress(80, "Moving to GPU")

            # Move to GPU
            self.pipeline = self.pipeline.to(self.device)

            # Enable optimizations
            if hasattr(self.pipeline, 'enable_attention_slicing'):
                self.pipeline.enable_attention_slicing()

            if hasattr(self.pipeline, 'enable_vae_slicing'):
                self.pipeline.enable_vae_slicing()

            self.current_model_id = model_id

            self._update_progress(100, "Model loaded")
            logger.info(f"Model {model_id} loaded successfully")
            return True

        except Exception as e:
            logger.error(f"Failed to load model {model_id}: {e}")
            self.pipeline = None
            self.current_model_id = None
            return False

    def generate_image(
        self,
        prompt: str,
        negative_prompt: str = "",
        width: int = 1024,
        height: int = 1024,
        steps: int = 30,
        cfg_scale: float = 7.5,
        seed: Optional[int] = None,
        output_dir: Optional[Path] = None,
    ) -> GenerationResult:
        """Generate an image"""
        if self.pipeline is None:
            return GenerationResult(
                success=False,
                error="No model loaded"
            )

        start_time = time.time()

        try:
            import torch
            from PIL import Image

            # Set up output
            output_dir = output_dir or Path.cwd() / 'outputs'
            output_dir.mkdir(parents=True, exist_ok=True)

            # Set up generator with seed
            generator = None
            if seed is not None:
                generator = torch.Generator(device=self.device).manual_seed(seed)

            self._update_progress(10, "Starting generation")

            # Progress callback for diffusers
            def step_callback(pipe, step, timestep, callback_kwargs):
                progress = int((step / steps) * 80) + 10
                self._update_progress(progress, f"Step {step}/{steps}")
                return callback_kwargs

            # Generate
            result = self.pipeline(
                prompt=prompt,
                negative_prompt=negative_prompt if negative_prompt else None,
                width=width,
                height=height,
                num_inference_steps=steps,
                guidance_scale=cfg_scale,
                generator=generator,
                callback_on_step_end=step_callback,
            )

            self._update_progress(90, "Saving image")

            # Save image
            image = result.images[0]
            timestamp = int(time.time())
            filename = f"gen_{timestamp}_{seed or 'random'}.png"
            output_path = output_dir / filename
            image.save(output_path)

            # Calculate hash
            with open(output_path, 'rb') as f:
                result_hash = hashlib.sha256(f.read()).hexdigest()

            processing_time = int((time.time() - start_time) * 1000)

            self._update_progress(100, "Complete")

            return GenerationResult(
                success=True,
                output_path=str(output_path),
                result_hash=result_hash,
                processing_time_ms=processing_time,
                metadata={
                    'prompt': prompt,
                    'negative_prompt': negative_prompt,
                    'width': width,
                    'height': height,
                    'steps': steps,
                    'cfg_scale': cfg_scale,
                    'seed': seed,
                    'model': self.current_model_id,
                }
            )

        except Exception as e:
            logger.error(f"Generation failed: {e}")
            return GenerationResult(
                success=False,
                error=str(e),
                processing_time_ms=int((time.time() - start_time) * 1000)
            )

    def download_model(
        self,
        huggingface_id: str,
        model_id: str,
        category: str = 'stable-diffusion'
    ) -> bool:
        """Download a model from HuggingFace"""
        self._update_progress(0, f"Starting download: {model_id}")

        try:
            from huggingface_hub import snapshot_download

            # Determine local path
            local_path = self.models_dir / category / model_id
            local_path.mkdir(parents=True, exist_ok=True)

            self._update_progress(10, "Connecting to HuggingFace")

            # Download with progress
            snapshot_download(
                huggingface_id,
                local_dir=local_path,
                local_dir_use_symlinks=False,
                ignore_patterns=["*.md", "*.txt", ".git*"],
            )

            self._update_progress(100, "Download complete")
            logger.info(f"Model {model_id} downloaded to {local_path}")
            return True

        except Exception as e:
            logger.error(f"Failed to download model: {e}")
            return False

    def unload_model(self):
        """Unload current model to free memory"""
        if self.pipeline is not None:
            import torch
            del self.pipeline
            self.pipeline = None
            self.current_model_id = None
            torch.cuda.empty_cache()
            logger.info("Model unloaded")


class AIWorker:
    """Worker that processes AI generation jobs"""

    def __init__(self):
        self.engine = AIEngine()
        self.is_working = False
        self.current_job = None
        self.progress = 0
        self.status = ""
        self.progress_callback: Optional[Callable] = None

    def set_progress_callback(self, callback: Callable):
        """Set callback for progress updates"""
        self.progress_callback = callback

        def engine_callback(progress: int, status: str):
            self.progress = progress
            self.status = status
            if self.progress_callback:
                self.progress_callback(progress, status)

        self.engine.set_progress_callback(engine_callback)

    def process_generation_job(self, job: Dict) -> Dict:
        """Process a generation job from the server"""
        self.is_working = True
        self.current_job = job
        self.progress = 0

        try:
            chunk_id = job.get('chunk_id')
            generation = job.get('generation', {})

            model_id = generation.get('model_id')
            huggingface_id = generation.get('huggingface_id')

            if not model_id or not huggingface_id:
                return {
                    'success': False,
                    'error': 'Missing model information',
                }

            # Load model if not already loaded
            if self.engine.current_model_id != model_id:
                if not self.engine.load_model(model_id, huggingface_id):
                    return {
                        'success': False,
                        'error': f'Failed to load model: {model_id}',
                    }

            # Generate
            result = self.engine.generate_image(
                prompt=generation.get('prompt', ''),
                negative_prompt=generation.get('negative_prompt', ''),
                width=generation.get('width', 1024),
                height=generation.get('height', 1024),
                steps=generation.get('steps', 30),
                cfg_scale=generation.get('cfg_scale', 7.5),
                seed=generation.get('seed'),
            )

            if result.success:
                return {
                    'success': True,
                    'result_hash': result.result_hash,
                    'result_file': result.output_path,
                    'metadata': result.metadata,
                    'processing_time_ms': result.processing_time_ms,
                }
            else:
                return {
                    'success': False,
                    'error': result.error,
                }

        except Exception as e:
            logger.error(f"Job processing failed: {e}")
            return {
                'success': False,
                'error': str(e),
            }
        finally:
            self.is_working = False
            self.current_job = None

    def get_status(self) -> Dict:
        """Get worker status"""
        return {
            'engine': self.engine.get_status(),
            'is_working': self.is_working,
            'progress': self.progress,
            'status': self.status,
            'installed_models': self.engine.list_installed_models(),
        }


def install_dependencies():
    """Install required Python packages"""
    import subprocess

    packages = [
        'torch',
        'torchvision',
        'diffusers',
        'transformers',
        'accelerate',
        'safetensors',
        'pillow',
        'huggingface_hub',
    ]

    # Try to install with CUDA support
    cuda_torch = 'torch torchvision --index-url https://download.pytorch.org/whl/cu121'

    print("Installing dependencies...")

    try:
        # Install PyTorch with CUDA
        subprocess.run(
            [sys.executable, '-m', 'pip', 'install'] + cuda_torch.split(),
            check=True
        )
    except:
        # Fallback to regular torch
        subprocess.run(
            [sys.executable, '-m', 'pip', 'install', 'torch', 'torchvision'],
            check=True
        )

    # Install other packages
    for pkg in packages[2:]:  # Skip torch/torchvision
        subprocess.run(
            [sys.executable, '-m', 'pip', 'install', pkg],
            check=True
        )

    print("Dependencies installed!")


if __name__ == '__main__':
    import argparse

    parser = argparse.ArgumentParser(description='AI Generation Engine')
    parser.add_argument('--install', action='store_true', help='Install dependencies')
    parser.add_argument('--status', action='store_true', help='Show engine status')
    parser.add_argument('--generate', type=str, help='Generate image from prompt')
    parser.add_argument('--model', type=str, default='sd-1.5', help='Model to use')
    parser.add_argument('--hf-id', type=str, default='runwayml/stable-diffusion-v1-5', help='HuggingFace model ID')

    args = parser.parse_args()

    if args.install:
        install_dependencies()
        sys.exit(0)

    logging.basicConfig(level=logging.INFO)

    engine = AIEngine()

    if args.status:
        status = engine.get_status()
        print(json.dumps(status, indent=2))
        sys.exit(0)

    if args.generate:
        def progress_cb(progress, status):
            print(f"[{progress}%] {status}")

        engine.set_progress_callback(progress_cb)

        if engine.load_model(args.model, args.hf_id):
            result = engine.generate_image(args.generate)
            print(json.dumps({
                'success': result.success,
                'output': result.output_path,
                'hash': result.result_hash,
                'error': result.error,
            }, indent=2))
