<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$slugs
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$slugs): Response
    {
        $user = Auth::user();

        // Jika belum login, redirect ke login
        if (!$user) {
            return redirect()->route('login');
        }

        // Jika tidak punya role atau slug tidak cocok
        if (!$user->role || !in_array($user->role->slug, $slugs)) {
         if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki hak akses.'
                ], 403);
            }
  // Redirect dengan pesan ke halaman sebelumnya
            return abort(403, 'Akses ditolak. Anda tidak memiliki hak akses.');
        }

        return $next($request);
    }
}
