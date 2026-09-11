<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpiry
{
    /**
     * Route name / URL yang dikecualikan dari pengecekan expiry.
     * Mencegah redirect loop dan membolehkan logout tetap berjalan.
     */
    protected array $except = [
        'filament.admin.pages.auth.force-change-password',
        'filament.admin.auth.logout',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Hanya cek jika user sudah login
        if ($user && !$this->isExcluded($request)) {
            if ($user->isPasswordExpired()) {
                return redirect()->route('filament.admin.pages.auth.force-change-password');
            }
        }

        return $next($request);
    }

    /**
     * Cek apakah request saat ini dikecualikan dari pengecekan.
     */
    protected function isExcluded(Request $request): bool
    {
        foreach ($this->except as $routeName) {
            if ($request->routeIs($routeName)) {
                return true;
            }
        }

        return false;
    }
}
