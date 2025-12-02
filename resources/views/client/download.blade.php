@extends('layouts.app')

@section('title', 'Download GPU Client')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-600/20 rounded-full mb-6">
            <i class="fas fa-download text-4xl text-purple-400"></i>
        </div>
        <h1 class="text-4xl font-bold text-white mb-4">Download GPU Share Client</h1>
        <p class="text-xl text-gray-400 max-w-2xl mx-auto">
            Share your GPU power and earn rewards. Monitor performance, control fans, and track earnings in real-time.
        </p>
    </div>

    @if(session('info'))
    <div class="bg-blue-600/20 border border-blue-500/30 rounded-lg p-4 mb-6">
        <p class="text-blue-300"><i class="fas fa-info-circle mr-2"></i>{{ session('info') }}</p>
    </div>
    @endif

    <!-- Download Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <!-- Windows -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden card-hover">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-blue-600/20 rounded-xl flex items-center justify-center">
                        <i class="fab fa-windows text-3xl text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Windows</h3>
                        <p class="text-gray-400 text-sm">v{{ $clientVersions['windows']['version'] }}</p>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-400 mb-4">
                    @foreach($clientVersions['windows']['requirements'] as $req)
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-0.5"></i>
                        <span>{{ $req }}</span>
                    </li>
                    @endforeach
                </ul>
                @if($clientVersions['windows']['available'] ?? false)
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i>{{ number_format($clientVersions['windows']['download_count'] ?? 0) }} downloads</span>
                        <span>{{ $clientVersions['windows']['size'] }}</span>
                    </div>
                    <a href="{{ route('client.download', 'windows') }}" class="block w-full py-3 bg-blue-600 hover:bg-blue-700 text-white text-center rounded-lg transition font-medium">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                @else
                    <div class="text-center py-3 bg-gray-700/50 rounded-lg text-gray-400">
                        <i class="fas fa-clock mr-2"></i>Coming Soon
                    </div>
                @endif
            </div>
        </div>

        <!-- Linux -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden card-hover">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-orange-600/20 rounded-xl flex items-center justify-center">
                        <i class="fab fa-linux text-3xl text-orange-400"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Linux</h3>
                        <p class="text-gray-400 text-sm">v{{ $clientVersions['linux']['version'] }}</p>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-400 mb-4">
                    @foreach($clientVersions['linux']['requirements'] as $req)
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-0.5"></i>
                        <span>{{ $req }}</span>
                    </li>
                    @endforeach
                </ul>
                @if($clientVersions['linux']['available'] ?? false)
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i>{{ number_format($clientVersions['linux']['download_count'] ?? 0) }} downloads</span>
                        <span>{{ $clientVersions['linux']['size'] }}</span>
                    </div>
                    <a href="{{ route('client.download', 'linux') }}" class="block w-full py-3 bg-orange-600 hover:bg-orange-700 text-white text-center rounded-lg transition font-medium">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                @else
                    <div class="text-center py-3 bg-gray-700/50 rounded-lg text-gray-400">
                        <i class="fas fa-clock mr-2"></i>Coming Soon
                    </div>
                @endif
            </div>
        </div>

        <!-- Source Code -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden card-hover">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-purple-600/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-code text-3xl text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Source Code</h3>
                        <p class="text-gray-400 text-sm">v{{ $clientVersions['source']['version'] }}</p>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-400 mb-4">
                    @foreach($clientVersions['source']['requirements'] as $req)
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-0.5"></i>
                        <span>{{ $req }}</span>
                    </li>
                    @endforeach
                </ul>
                @if($clientVersions['source']['available'] ?? false)
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-download mr-1"></i>{{ number_format($clientVersions['source']['download_count'] ?? 0) }} downloads</span>
                        <span>{{ $clientVersions['source']['size'] }}</span>
                    </div>
                    <a href="{{ route('client.download', 'source') }}" class="block w-full py-3 bg-purple-600 hover:bg-purple-700 text-white text-center rounded-lg transition font-medium">
                        <i class="fas fa-code-branch mr-2"></i>Get Source
                    </a>
                @else
                    <div class="text-center py-3 bg-gray-700/50 rounded-lg text-gray-400">
                        <i class="fas fa-clock mr-2"></i>Coming Soon
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Version History (if available) -->
    @if(isset($allVersions) && $allVersions->count() > 0)
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-12">
        <h2 class="text-2xl font-bold text-white mb-6">
            <i class="fas fa-history text-purple-400 mr-3"></i>Version History
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Version</th>
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Platform</th>
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Size</th>
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Downloads</th>
                        <th class="text-left py-3 px-4 text-gray-400 font-medium">Released</th>
                        <th class="text-right py-3 px-4 text-gray-400 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allVersions->flatten() as $ver)
                    @if($ver->is_active && $ver->fileExists())
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
                        <td class="py-3 px-4">
                            <span class="text-white font-medium">v{{ $ver->version }}</span>
                            @if($ver->is_latest)
                                <span class="ml-2 px-2 py-0.5 bg-green-600/20 text-green-400 text-xs rounded">Latest</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-gray-300">
                            @if($ver->platform === 'windows')
                                <i class="fab fa-windows text-blue-400 mr-1"></i> Windows
                            @elseif($ver->platform === 'linux')
                                <i class="fab fa-linux text-orange-400 mr-1"></i> Linux
                            @else
                                <i class="fas fa-code text-purple-400 mr-1"></i> Source
                            @endif
                        </td>
                        <td class="py-3 px-4 text-gray-400">{{ $ver->formatted_file_size }}</td>
                        <td class="py-3 px-4 text-gray-400">{{ number_format($ver->download_count) }}</td>
                        <td class="py-3 px-4 text-gray-400">{{ $ver->created_at->format('M d, Y') }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('client.download.file', ['platform' => $ver->platform, 'version' => $ver->version]) }}"
                               class="text-purple-400 hover:text-purple-300 transition">
                                <i class="fas fa-download mr-1"></i>Download
                            </a>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Features -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-white mb-6 text-center">Client Features</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($features as $feature)
            <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                <div class="w-12 h-12 bg-purple-600/20 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas {{ $feature['icon'] }} text-xl text-purple-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">{{ $feature['title'] }}</h3>
                <p class="text-gray-400 text-sm">{{ $feature['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Start Guide -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-12">
        <h2 class="text-2xl font-bold text-white mb-6">
            <i class="fas fa-rocket text-purple-400 mr-3"></i>Quick Start Guide
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 text-white font-bold">1</div>
                <h4 class="font-semibold text-white mb-2">Download</h4>
                <p class="text-gray-400 text-sm">Download the client for your operating system</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 text-white font-bold">2</div>
                <h4 class="font-semibold text-white mb-2">Install & Login</h4>
                <p class="text-gray-400 text-sm">Run the installer and login with your account</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 text-white font-bold">3</div>
                <h4 class="font-semibold text-white mb-2">Configure</h4>
                <p class="text-gray-400 text-sm">Set your GPU usage limits and preferences</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 text-white font-bold">4</div>
                <h4 class="font-semibold text-white mb-2">Start Earning</h4>
                <p class="text-gray-400 text-sm">Click Start and begin earning rewards!</p>
            </div>
        </div>
    </div>

    <!-- Status Indicators -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-12">
        <h2 class="text-2xl font-bold text-white mb-6">
            <i class="fas fa-traffic-light text-purple-400 mr-3"></i>Status Indicators
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="flex items-center gap-3 p-4 bg-gray-750 rounded-lg">
                <div class="w-4 h-4 rounded-full bg-gray-500"></div>
                <div>
                    <p class="text-white font-medium">Idle</p>
                    <p class="text-gray-400 text-xs">Waiting for jobs</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4 bg-gray-750 rounded-lg">
                <div class="w-4 h-4 rounded-full bg-blue-500 animate-pulse"></div>
                <div>
                    <p class="text-white font-medium">Receiving</p>
                    <p class="text-gray-400 text-xs">Downloading job</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4 bg-gray-750 rounded-lg">
                <div class="w-4 h-4 rounded-full bg-green-500 animate-pulse"></div>
                <div>
                    <p class="text-white font-medium">Processing</p>
                    <p class="text-gray-400 text-xs">GPU working</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4 bg-gray-750 rounded-lg">
                <div class="w-4 h-4 rounded-full bg-purple-500 animate-pulse"></div>
                <div>
                    <p class="text-white font-medium">Uploading</p>
                    <p class="text-gray-400 text-xs">Sending results</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-4 bg-gray-750 rounded-lg">
                <div class="w-4 h-4 rounded-full bg-red-500"></div>
                <div>
                    <p class="text-white font-medium">Error</p>
                    <p class="text-gray-400 text-xs">Job failed</p>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-yellow-600/20 border border-yellow-500/30 rounded-lg">
            <h4 class="font-semibold text-yellow-400 mb-2">
                <i class="fas fa-exclamation-triangle mr-2"></i>Important Notes
            </h4>
            <ul class="text-gray-300 text-sm space-y-1">
                <li>- Failed jobs do not count towards earnings</li>
                <li>- If jobs fail frequently, check your internet connection and GPU health</li>
                <li>- Avoid overclocking your GPU as it may cause instability</li>
                <li>- Keep temperatures below 85°C for optimal performance</li>
            </ul>
        </div>
    </div>

    <!-- System Requirements -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <h2 class="text-2xl font-bold text-white mb-6">
            <i class="fas fa-cogs text-purple-400 mr-3"></i>System Requirements
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-white mb-3">Minimum Requirements</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li class="flex items-center gap-2"><i class="fas fa-microchip text-gray-500 w-5"></i> NVIDIA GTX 1060 6GB or higher</li>
                    <li class="flex items-center gap-2"><i class="fas fa-memory text-gray-500 w-5"></i> 8GB System RAM</li>
                    <li class="flex items-center gap-2"><i class="fas fa-hdd text-gray-500 w-5"></i> 50GB Free Disk Space</li>
                    <li class="flex items-center gap-2"><i class="fas fa-wifi text-gray-500 w-5"></i> 10 Mbps Internet Connection</li>
                    <li class="flex items-center gap-2"><i class="fab fa-windows text-gray-500 w-5"></i> Windows 10/11 or Ubuntu 20.04+</li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold text-white mb-3">Recommended Requirements</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li class="flex items-center gap-2"><i class="fas fa-microchip text-green-500 w-5"></i> NVIDIA RTX 3070 or higher</li>
                    <li class="flex items-center gap-2"><i class="fas fa-memory text-green-500 w-5"></i> 16GB+ System RAM</li>
                    <li class="flex items-center gap-2"><i class="fas fa-hdd text-green-500 w-5"></i> 200GB+ SSD Storage</li>
                    <li class="flex items-center gap-2"><i class="fas fa-wifi text-green-500 w-5"></i> 100 Mbps+ Internet Connection</li>
                    <li class="flex items-center gap-2"><i class="fas fa-plug text-green-500 w-5"></i> 750W+ Power Supply</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
