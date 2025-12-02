"""
API Client for GPU Sharing Platform
"""
import requests
import logging
from typing import Dict, Optional, Any
from config import API_BASE_URL, API_TIMEOUT, CLIENT_VERSION

logger = logging.getLogger(__name__)


class APIError(Exception):
    """Custom API Error"""
    def __init__(self, message: str, status_code: int = 0, response: Dict = None):
        self.message = message
        self.status_code = status_code
        self.response = response or {}
        super().__init__(self.message)


class APIClient:
    def __init__(self):
        self.base_url = API_BASE_URL
        self.token: Optional[str] = None
        self.session = requests.Session()
        self.session.headers.update({
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Client-Version': CLIENT_VERSION,
        })

    def set_token(self, token: str):
        """Set authentication token"""
        self.token = token
        self.session.headers['Authorization'] = f'Bearer {token}'

    def _request(self, method: str, endpoint: str, data: Dict = None) -> Dict:
        """Make API request"""
        url = f'{self.base_url}{endpoint}'

        try:
            response = self.session.request(
                method=method,
                url=url,
                json=data,
                timeout=API_TIMEOUT
            )

            result = response.json()

            if not response.ok:
                raise APIError(
                    result.get('message', 'Unknown error'),
                    response.status_code,
                    result
                )

            return result

        except requests.exceptions.Timeout:
            raise APIError('Request timeout')
        except requests.exceptions.ConnectionError:
            raise APIError('Connection error')
        except requests.exceptions.JSONDecodeError:
            raise APIError('Invalid response from server')

    # Auth endpoints
    def login(self, email: str, password: str) -> Dict:
        """Login and get token"""
        result = self._request('POST', '/login', {
            'email': email,
            'password': password,
        })
        if result.get('success') and result.get('data', {}).get('token'):
            self.set_token(result['data']['token'])
        return result

    def register(self, name: str, email: str, password: str, referral_code: str = None) -> Dict:
        """Register new account"""
        data = {
            'name': name,
            'email': email,
            'password': password,
            'password_confirmation': password,
        }
        if referral_code:
            data['referral_code'] = referral_code

        result = self._request('POST', '/register', data)
        if result.get('success') and result.get('data', {}).get('token'):
            self.set_token(result['data']['token'])
        return result

    def get_me(self) -> Dict:
        """Get current user info"""
        return self._request('GET', '/me')

    # Node endpoints
    def register_node(self, registration_data: Dict) -> Dict:
        """Register GPU node"""
        data = {
            **registration_data,
            'client_version': CLIENT_VERSION,
        }
        return self._request('POST', '/nodes/register', data)

    def heartbeat(self, node_id: str, status: str = 'online', metrics: Dict = None) -> Dict:
        """Send heartbeat"""
        data = {
            'node_id': node_id,
            'status': status,
        }
        if metrics:
            data.update(metrics)
        return self._request('POST', '/nodes/heartbeat', data)

    def submit_benchmark(self, node_id: str, score: int, details: Dict = None) -> Dict:
        """Submit benchmark results"""
        return self._request('POST', '/nodes/benchmark', {
            'node_id': node_id,
            'benchmark_score': score,
            'benchmark_details': details or {},
        })

    def submit_verification(self, node_id: str, task_id: int, result_hash: str, time_taken: int) -> Dict:
        """Submit verification task result"""
        return self._request('POST', '/nodes/verification', {
            'node_id': node_id,
            'task_id': task_id,
            'result_hash': result_hash,
            'time_taken': time_taken,
        })

    def disconnect_node(self, node_id: str) -> Dict:
        """Disconnect node"""
        return self._request('POST', '/nodes/disconnect', {
            'node_id': node_id,
        })

    # Job endpoints
    def get_work(self, node_id: str) -> Dict:
        """Get work assignment"""
        return self._request('GET', f'/jobs/work?node_id={node_id}')

    def start_work(self, node_id: str, chunk_id: str) -> Dict:
        """Mark work as started"""
        return self._request('POST', '/jobs/start', {
            'node_id': node_id,
            'chunk_id': chunk_id,
        })

    def update_progress(self, node_id: str, chunk_id: str, progress: int) -> Dict:
        """Update work progress"""
        return self._request('POST', '/jobs/progress', {
            'node_id': node_id,
            'chunk_id': chunk_id,
            'progress': progress,
        })

    def submit_work(self, node_id: str, chunk_id: str, result_hash: str,
                    result_file: str = None, metadata: Dict = None) -> Dict:
        """Submit completed work"""
        return self._request('POST', '/jobs/submit', {
            'node_id': node_id,
            'chunk_id': chunk_id,
            'result_hash': result_hash,
            'result_file': result_file,
            'metadata': metadata or {},
        })

    def report_error(self, node_id: str, chunk_id: str, error_message: str) -> Dict:
        """Report work error"""
        return self._request('POST', '/jobs/error', {
            'node_id': node_id,
            'chunk_id': chunk_id,
            'error_message': error_message,
        })

    # Earnings endpoints
    def get_earnings_summary(self) -> Dict:
        """Get earnings summary"""
        return self._request('GET', '/earnings/summary')

    # Pool stats
    def get_pool_stats(self) -> Dict:
        """Get pool statistics (public)"""
        return self._request('GET', '/pool/stats')
