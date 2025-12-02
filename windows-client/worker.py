"""
GPU Worker - Handles job processing and verification
"""
import hashlib
import time
import logging
import random
from typing import Dict, Optional, Callable
from concurrent.futures import ThreadPoolExecutor

logger = logging.getLogger(__name__)


class GPUWorker:
    """Handles GPU work execution"""

    def __init__(self):
        self.current_job: Optional[Dict] = None
        self.is_working = False
        self.progress = 0
        self.progress_callback: Optional[Callable] = None

    def set_progress_callback(self, callback: Callable):
        """Set callback for progress updates"""
        self.progress_callback = callback

    def _update_progress(self, progress: int):
        """Update and report progress"""
        self.progress = progress
        if self.progress_callback:
            self.progress_callback(progress)

    def run_benchmark(self, iterations: int = 1000, matrix_size: int = 1024) -> Dict:
        """
        Run GPU benchmark to measure performance.
        In real implementation, this would use CUDA/OpenCL for actual GPU compute.
        """
        logger.info(f"Starting benchmark: {iterations} iterations, matrix size {matrix_size}")
        start_time = time.time()

        try:
            # Simulate GPU computation
            # In production, replace with actual CUDA/OpenCL operations
            import numpy as np

            total_ops = 0
            for i in range(iterations):
                # Matrix operations (CPU simulation of GPU work)
                a = np.random.rand(matrix_size, matrix_size).astype(np.float32)
                b = np.random.rand(matrix_size, matrix_size).astype(np.float32)
                c = np.dot(a, b)
                total_ops += matrix_size * matrix_size * matrix_size * 2

                if i % 100 == 0:
                    self._update_progress(int((i / iterations) * 100))

        except ImportError:
            # Fallback without numpy
            total_ops = iterations * matrix_size * matrix_size
            for i in range(iterations):
                # Simple computation
                result = sum(range(matrix_size))
                if i % 100 == 0:
                    self._update_progress(int((i / iterations) * 100))

        elapsed = time.time() - start_time
        score = int(total_ops / elapsed / 1000000)  # MFLOPS approximation

        logger.info(f"Benchmark complete: score={score}, time={elapsed:.2f}s")

        return {
            'score': score,
            'elapsed_time': elapsed,
            'iterations': iterations,
            'matrix_size': matrix_size,
        }

    def process_job(self, chunk: Dict) -> Dict:
        """
        Process a render job chunk.
        In real implementation, this would interface with actual render engines.
        """
        self.current_job = chunk
        self.is_working = True
        self.progress = 0

        chunk_id = chunk.get('chunk_id', 'unknown')
        job_type = chunk.get('job_type', 'image')
        params = chunk.get('params', {})

        logger.info(f"Processing job chunk: {chunk_id}, type: {job_type}")

        try:
            start_time = time.time()

            # Simulate different job types
            if job_type == 'image':
                result = self._process_image_job(params)
            elif job_type == 'video':
                result = self._process_video_job(params)
            else:
                result = self._process_generic_job(params)

            elapsed = time.time() - start_time

            # Generate result hash
            result_data = f"{chunk_id}:{job_type}:{result}:{elapsed}"
            result_hash = hashlib.sha256(result_data.encode()).hexdigest()

            self._update_progress(100)

            return {
                'success': True,
                'result_hash': result_hash,
                'elapsed_time': elapsed,
                'metadata': {
                    'job_type': job_type,
                    'chunk_id': chunk_id,
                },
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

    def _process_image_job(self, params: Dict) -> str:
        """Process image generation job"""
        # Simulate image generation work
        steps = params.get('steps', 50)
        for step in range(steps):
            time.sleep(0.1)  # Simulate GPU work
            self._update_progress(int((step / steps) * 100))

        return f"image_result_{random.randint(1000, 9999)}"

    def _process_video_job(self, params: Dict) -> str:
        """Process video generation job"""
        frames = params.get('frames', 30)
        for frame in range(frames):
            time.sleep(0.2)  # Simulate GPU work
            self._update_progress(int((frame / frames) * 100))

        return f"video_result_{random.randint(1000, 9999)}"

    def _process_generic_job(self, params: Dict) -> str:
        """Process generic render job"""
        duration = params.get('estimated_seconds', 10)
        steps = max(10, duration)

        for step in range(steps):
            time.sleep(duration / steps)
            self._update_progress(int((step / steps) * 100))

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

        # Deterministic benchmark based on seed
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

            if nonce > 10000000:  # Prevent infinite loop
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
