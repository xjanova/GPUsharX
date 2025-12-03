"""
GPU Worker - Handles job processing and verification
Integrates with AI Engine for actual image/video generation
"""
import hashlib
import time
import logging
import random
import os
from typing import Dict, Optional, Callable
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

logger = logging.getLogger(__name__)

# Try to import AI engine
try:
    from ai_engine import AIWorker, AIEngine
    AI_ENGINE_AVAILABLE = True
    logger.info("AI Engine available")
except ImportError:
    AI_ENGINE_AVAILABLE = False
    logger.warning("AI Engine not available - running in demo mode")


class GPUWorker:
    """Handles GPU work execution with real AI generation support"""

    def __init__(self, use_ai_engine: bool = True):
        self.current_job: Optional[Dict] = None
        self.is_working = False
        self.progress = 0
        self.status = ""
        self.progress_callback: Optional[Callable] = None
        self.power_limit = 100

        # Import model manager for local models
        try:
            from model_manager import ModelManager
            self.model_manager = ModelManager()
            logger.info(f"Model Manager initialized, models dir: {self.model_manager.models_dir}")
        except Exception as e:
            self.model_manager = None
            logger.warning(f"Model Manager not available: {e}")

        # Initialize AI engine if available
        self.ai_worker = None
        self.use_real_ai = use_ai_engine and AI_ENGINE_AVAILABLE

        if self.use_real_ai:
            try:
                self.ai_worker = AIWorker()
                # Set models directory from model manager
                if self.model_manager:
                    self.ai_worker.engine.models_dir = self.model_manager.models_dir
                logger.info("AI Worker initialized")
            except Exception as e:
                logger.error(f"Failed to initialize AI Worker: {e}")
                self.use_real_ai = False

    def set_progress_callback(self, callback: Callable):
        """Set callback for progress updates"""
        self.progress_callback = callback

        if self.ai_worker:
            def ai_callback(progress: int, status: str):
                self.progress = progress
                self.status = status
                if self.progress_callback:
                    self.progress_callback(progress)

            self.ai_worker.set_progress_callback(ai_callback)

    def _update_progress(self, progress: int, status: str = ""):
        """Update and report progress"""
        self.progress = progress
        self.status = status
        if self.progress_callback:
            self.progress_callback(progress)

    def set_power_limit(self, power_percent: int):
        """Set GPU power limit percentage (for future use)"""
        self.power_limit = max(10, min(100, power_percent))
        logger.info(f"Power limit set to {self.power_limit}%")

    def get_engine_status(self) -> Dict:
        """Get AI engine status"""
        if self.ai_worker:
            return self.ai_worker.get_status()
        return {
            'ready': False,
            'reason': 'AI Engine not available',
            'demo_mode': True,
        }

    def run_benchmark(self, iterations: int = 1000, matrix_size: int = 1024) -> Dict:
        """
        Run GPU benchmark to measure performance.
        Uses actual GPU if available via PyTorch.
        """
        logger.info(f"Starting benchmark: {iterations} iterations, matrix size {matrix_size}")
        start_time = time.time()

        try:
            # Try GPU benchmark with PyTorch
            import torch
            if torch.cuda.is_available():
                logger.info("Running GPU benchmark with CUDA")
                device = torch.device('cuda')

                total_ops = 0
                for i in range(iterations):
                    a = torch.rand(matrix_size, matrix_size, device=device, dtype=torch.float32)
                    b = torch.rand(matrix_size, matrix_size, device=device, dtype=torch.float32)
                    c = torch.matmul(a, b)
                    torch.cuda.synchronize()
                    total_ops += matrix_size * matrix_size * matrix_size * 2

                    if i % 100 == 0:
                        self._update_progress(int((i / iterations) * 100), "GPU Benchmark")

                torch.cuda.empty_cache()
            else:
                raise ImportError("CUDA not available")

        except ImportError:
            # Fallback to numpy
            try:
                import numpy as np
                logger.info("Running CPU benchmark with NumPy")

                total_ops = 0
                for i in range(iterations):
                    a = np.random.rand(matrix_size, matrix_size).astype(np.float32)
                    b = np.random.rand(matrix_size, matrix_size).astype(np.float32)
                    c = np.dot(a, b)
                    total_ops += matrix_size * matrix_size * matrix_size * 2

                    if i % 100 == 0:
                        self._update_progress(int((i / iterations) * 100), "CPU Benchmark")

            except ImportError:
                # Simple fallback
                logger.info("Running basic benchmark")
                total_ops = iterations * matrix_size * matrix_size
                for i in range(iterations):
                    result = sum(range(matrix_size))
                    if i % 100 == 0:
                        self._update_progress(int((i / iterations) * 100), "Basic Benchmark")

        elapsed = time.time() - start_time
        score = int(total_ops / elapsed / 1000000)  # MFLOPS approximation

        logger.info(f"Benchmark complete: score={score}, time={elapsed:.2f}s")

        return {
            'score': score,
            'elapsed_time': elapsed,
            'iterations': iterations,
            'matrix_size': matrix_size,
            'gpu_available': self.use_real_ai and self.ai_worker and self.ai_worker.engine.device == 'cuda',
        }

    def process_job(self, chunk: Dict) -> Dict:
        """
        Process a render job chunk.
        Uses real AI engine if available, otherwise falls back to demo mode.
        """
        self.current_job = chunk
        self.is_working = True
        self.progress = 0

        chunk_id = chunk.get('chunk_id', 'unknown')
        job_type = chunk.get('job_type', 'image')
        generation = chunk.get('generation', {})

        logger.info(f"Processing job chunk: {chunk_id}, type: {job_type}")

        try:
            start_time = time.time()

            # Try real AI generation
            if self.use_real_ai and self.ai_worker and job_type in ['image', 'video']:
                result = self._process_with_ai_engine(chunk)
            else:
                # Fallback to demo mode
                if job_type == 'image':
                    result = self._process_image_job_demo(generation)
                elif job_type == 'video':
                    result = self._process_video_job_demo(generation)
                else:
                    result = self._process_generic_job(generation)

                # Generate demo result hash
                result_data = f"{chunk_id}:{job_type}:{result}:{time.time()}"
                result_hash = hashlib.sha256(result_data.encode()).hexdigest()

                elapsed = time.time() - start_time
                self._update_progress(100, "Complete")

                return {
                    'success': True,
                    'result_hash': result_hash,
                    'elapsed_time': elapsed,
                    'demo_mode': True,
                    'metadata': {
                        'job_type': job_type,
                        'chunk_id': chunk_id,
                    },
                }

            return result

        except Exception as e:
            logger.error(f"Job processing failed: {e}")
            return {
                'success': False,
                'error': str(e),
            }
        finally:
            self.is_working = False
            self.current_job = None

    def _process_with_ai_engine(self, chunk: Dict) -> Dict:
        """Process job using real AI engine"""
        try:
            result = self.ai_worker.process_generation_job(chunk)
            return result
        except Exception as e:
            logger.error(f"AI engine processing failed: {e}")
            raise

    def _process_image_job_demo(self, params: Dict) -> str:
        """Process image generation job (demo mode)"""
        steps = params.get('steps', 30)
        prompt = params.get('prompt', 'demo image')

        logger.info(f"Demo mode: Generating image with {steps} steps")

        for step in range(steps):
            time.sleep(0.05)  # Faster demo
            self._update_progress(int((step / steps) * 100), f"Step {step + 1}/{steps}")

        return f"demo_image_{random.randint(1000, 9999)}"

    def _process_video_job_demo(self, params: Dict) -> str:
        """Process video generation job (demo mode)"""
        frames = params.get('frames', 16)

        logger.info(f"Demo mode: Generating video with {frames} frames")

        for frame in range(frames):
            time.sleep(0.1)
            self._update_progress(int((frame / frames) * 100), f"Frame {frame + 1}/{frames}")

        return f"demo_video_{random.randint(1000, 9999)}"

    def _process_generic_job(self, params: Dict) -> str:
        """Process generic render job"""
        duration = params.get('estimated_seconds', 5)
        steps = max(10, int(duration))

        for step in range(steps):
            time.sleep(duration / steps)
            self._update_progress(int((step / steps) * 100), f"Processing...")

        return f"render_result_{random.randint(1000, 9999)}"

    def process_verification(self, task: Dict) -> Dict:
        """Process anti-cheat verification task"""
        task_type = task.get('type', 'proof_of_work')
        params = task.get('params', {})

        logger.info(f"Processing verification task: {task_type}")

        start_time = time.time()

        try:
            if task_type == 'benchmark':
                result = self._verify_benchmark(params)
            elif task_type == 'proof_of_work':
                result = self._verify_proof_of_work(params)
            else:
                result = self._verify_generic(params)

            elapsed = int(time.time() - start_time)

            return {
                'success': True,
                'result_hash': result,
                'time_taken': elapsed,
            }

        except Exception as e:
            logger.error(f"Verification failed: {e}")
            return {
                'success': False,
                'error': str(e),
            }

    def _verify_benchmark(self, params: Dict) -> str:
        """Run benchmark verification"""
        seed = params.get('seed', 0)
        iterations = params.get('iterations', 100)
        matrix_size = params.get('matrix_size', 256)

        random.seed(seed)
        result = 0
        for _ in range(iterations):
            result += sum(random.randint(0, matrix_size) for _ in range(matrix_size))

        return hashlib.sha256(str(result).encode()).hexdigest()

    def _verify_proof_of_work(self, params: Dict) -> str:
        """Solve proof of work challenge"""
        challenge = params.get('challenge', '')
        difficulty = params.get('difficulty', 4)

        nonce = 0
        prefix = '0' * difficulty

        while True:
            test = f"{challenge}:{nonce}"
            hash_result = hashlib.sha256(test.encode()).hexdigest()
            if hash_result.startswith(prefix):
                return hash_result
            nonce += 1

            if nonce > 10000000:
                raise Exception("Could not solve proof of work")

    def _verify_generic(self, params: Dict) -> str:
        """Generic verification"""
        data = str(params)
        return hashlib.sha256(data.encode()).hexdigest()

    def cancel_current_job(self):
        """Cancel current running job"""
        self.is_working = False
        self.current_job = None
        logger.info("Job cancelled")

    def download_model(self, huggingface_id: str, model_id: str, category: str = 'stable-diffusion') -> bool:
        """Download a model from HuggingFace"""
        if not self.ai_worker:
            logger.error("AI Worker not available for model download")
            return False

        return self.ai_worker.engine.download_model(huggingface_id, model_id, category)

    def load_model(self, model_id: str, huggingface_id: str, model_type: str = 'stable_diffusion') -> bool:
        """Load an AI model"""
        if not self.ai_worker:
            logger.error("AI Worker not available for model loading")
            return False

        return self.ai_worker.engine.load_model(model_id, huggingface_id, model_type)

    def get_installed_models(self) -> list:
        """Get list of installed models"""
        if not self.ai_worker:
            return []
        return self.ai_worker.engine.list_installed_models()
