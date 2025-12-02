<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Routes that should be accessible without installation
     */
    protected $except = [
        'install',
        'install/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is an install route
        if ($this->isInstallRoute($request)) {
            return $next($request);
        }

        // Check if system is installed
        if (!$this->isInstalled()) {
            return redirect()->route('install.index');
        }

        return $next($request);
    }

    protected function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }

    protected function isInstallRoute(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }
        return false;
    }
}
