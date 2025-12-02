<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class InstallMiddleware
{
    /**
     * Handle an incoming request.
     * Force file-based session during installation to avoid database dependency.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If app is not installed, force file-based drivers
        if (!File::exists(storage_path('installed'))) {
            config(['session.driver' => 'file']);
            config(['cache.default' => 'file']);
        }

        return $next($request);
    }
}
