<?php

namespace App\Http\Controllers;

use App\Models\ClientVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(): View
    {
        // Get latest versions for each platform from database
        $latestVersions = [
            'windows' => ClientVersion::getLatestForPlatform('windows'),
            'linux' => ClientVersion::getLatestForPlatform('linux'),
            'source' => ClientVersion::getLatestForPlatform('source'),
        ];

        // Fallback static data if no versions in database
        $clientVersions = [];

        foreach (['windows', 'linux', 'source'] as $platform) {
            if ($latestVersions[$platform]) {
                $v = $latestVersions[$platform];
                $clientVersions[$platform] = [
                    'version' => $v->version,
                    'size' => $v->formatted_file_size,
                    'filename' => $v->filename,
                    'available' => $v->fileExists(),
                    'download_count' => $v->download_count,
                    'release_notes' => $v->release_notes,
                    'requirements' => $this->getRequirements($platform, $v),
                ];
            } else {
                $clientVersions[$platform] = $this->getDefaultVersionInfo($platform);
            }
        }

        $features = [
            [
                'icon' => 'fa-microchip',
                'title' => 'GPU Monitoring',
                'description' => 'Real-time monitoring of GPU temperature, power usage, and memory.',
            ],
            [
                'icon' => 'fa-fan',
                'title' => 'Fan Control',
                'description' => 'Adjust GPU and CPU fan speeds for optimal cooling.',
            ],
            [
                'icon' => 'fa-network-wired',
                'title' => 'Bandwidth Monitor',
                'description' => 'Track upload/download bandwidth usage.',
            ],
            [
                'icon' => 'fa-bolt',
                'title' => 'Power Tracking',
                'description' => 'Monitor power consumption and estimate electricity costs.',
            ],
            [
                'icon' => 'fa-shield-alt',
                'title' => 'Safe & Secure',
                'description' => 'Open-source code with secure API communication.',
            ],
            [
                'icon' => 'fa-coins',
                'title' => 'Auto Earnings',
                'description' => 'Automatically receive earnings for completed jobs.',
            ],
        ];

        // Get all versions for version history
        $allVersions = ClientVersion::active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('platform');

        return view('client.download', compact('clientVersions', 'features', 'allVersions', 'latestVersions'));
    }

    public function download(string $platform)
    {
        $version = ClientVersion::getLatestForPlatform($platform);

        if (!$version) {
            return redirect()->back()->with('info', 'Client download coming soon! For now, you can build from source.');
        }

        return $this->downloadFile($version);
    }

    public function downloadVersion(string $platform, string $versionNumber)
    {
        $version = ClientVersion::where('platform', $platform)
            ->where('version', $versionNumber)
            ->where('is_active', true)
            ->first();

        if (!$version) {
            abort(404, 'Version not found');
        }

        return $this->downloadFile($version);
    }

    protected function downloadFile(ClientVersion $version): StreamedResponse
    {
        if (!$version->fileExists()) {
            abort(404, 'File not found');
        }

        // Increment download count
        $version->incrementDownloads();

        $filePath = Storage::disk('public')->path($version->file_path);

        return response()->streamDownload(function () use ($filePath) {
            $stream = fopen($filePath, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, $version->filename, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $version->file_size,
            'Content-Disposition' => 'attachment; filename="' . $version->filename . '"',
        ]);
    }

    protected function getRequirements(string $platform, ClientVersion $version): array
    {
        $base = [
            'windows' => [
                'Windows 10/11 (64-bit)',
                'NVIDIA GPU (GTX 1060 or higher)',
                "{$version->min_ram} RAM minimum",
                'NVIDIA Drivers with CUDA',
            ],
            'linux' => [
                'Ubuntu 20.04+ / Debian 11+',
                'NVIDIA GPU with CUDA support',
                "{$version->min_ram} RAM minimum",
                'Python 3.10+',
            ],
            'source' => [
                'Python 3.10+',
                'pip packages: see requirements.txt',
                'CUDA Toolkit 11.8+',
                "{$version->min_gpu_memory} GPU Memory minimum",
            ],
        ];

        return $base[$platform] ?? [];
    }

    protected function getDefaultVersionInfo(string $platform): array
    {
        $defaults = [
            'windows' => [
                'version' => '1.0.0',
                'size' => 'Coming Soon',
                'filename' => 'GPUShareClient-Windows-1.0.0.exe',
                'available' => false,
                'download_count' => 0,
                'release_notes' => null,
                'requirements' => [
                    'Windows 10/11 (64-bit)',
                    'NVIDIA GPU (GTX 1060 or higher)',
                    '8GB RAM minimum',
                    'Python 3.10+ (bundled)',
                ],
            ],
            'linux' => [
                'version' => '1.0.0',
                'size' => 'Coming Soon',
                'filename' => 'GPUShareClient-Linux-1.0.0.tar.gz',
                'available' => false,
                'download_count' => 0,
                'release_notes' => null,
                'requirements' => [
                    'Ubuntu 20.04+ / Debian 11+',
                    'NVIDIA GPU with CUDA support',
                    '8GB RAM minimum',
                    'Python 3.10+',
                ],
            ],
            'source' => [
                'version' => '1.0.0',
                'size' => 'Coming Soon',
                'filename' => 'GPUShareClient-Source-1.0.0.zip',
                'available' => false,
                'download_count' => 0,
                'release_notes' => null,
                'requirements' => [
                    'Python 3.10+',
                    'pip packages: see requirements.txt',
                    'CUDA Toolkit 11.8+',
                ],
            ],
        ];

        return $defaults[$platform] ?? [];
    }

    // API endpoint to check for updates
    public function checkUpdate(Request $request)
    {
        $platform = $request->input('platform', 'windows');
        $currentVersion = $request->input('version');

        $latest = ClientVersion::getLatestForPlatform($platform);

        if (!$latest) {
            return response()->json([
                'update_available' => false,
            ]);
        }

        $updateAvailable = version_compare($latest->version, $currentVersion, '>');

        return response()->json([
            'update_available' => $updateAvailable,
            'latest_version' => $latest->version,
            'download_url' => $latest->download_url,
            'release_notes' => $latest->release_notes,
            'file_size' => $latest->file_size,
            'checksum_sha256' => $latest->checksum_sha256,
        ]);
    }
}
