<?php
// app/Http/Middleware/TrackVisitor.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Visitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tracking untuk request tertentu
        if ($this->shouldSkipTracking($request)) {
            return $next($request);
        }

        try {
            $ip = $request->ip();
            $currentTime = now();

            // Cek apakah IP ini sudah mengakses dalam 30 menit terakhir
            $existingVisit = Visitor::where('ip', $ip)
                ->where('created_at', '>=', $currentTime->copy()->subMinutes(30))
                ->first();

            // Jika belum ada kunjungan dalam 30 menit terakhir, baru catat
            if (!$existingVisit) {
                $this->trackVisitor($request, $ip);
            }
        } catch (Exception $e) {
            // Log error tapi jangan ganggu request utama
            Log::error('Visitor tracking failed: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Track visitor data
     */
    private function trackVisitor(Request $request, string $ip): void
    {
        try {
            // Skip untuk IP lokal
            if ($this->isLocalIp($ip)) {
                $this->createVisitorRecord($request, $ip, [
                    'country' => 'Local',
                    'city' => 'Local',
                    'lat' => null,
                    'lon' => null
                ]);
                return;
            }

            // Dapatkan data lokasi dari IP
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
            
            if ($response->successful()) {
                $locationData = $response->json();
                
                // Validasi response dari ip-api.com
                if (isset($locationData['status']) && $locationData['status'] === 'success') {
                    $this->createVisitorRecord($request, $ip, $locationData);
                } else {
                    // Fallback jika gagal
                    $this->createVisitorRecord($request, $ip, []);
                }
            } else {
                // Jika API gagal, tetap catat tanpa lokasi
                $this->createVisitorRecord($request, $ip, []);
            }
        } catch (Exception $e) {
            Log::error('Location API failed: ' . $e->getMessage());
            // Tetap catat visitor tanpa lokasi
            $this->createVisitorRecord($request, $ip, []);
        }
    }

    /**
     * Create visitor record in database
     */
    private function createVisitorRecord(Request $request, string $ip, array $locationData): void
    {
        Visitor::create([
            'ip' => $ip,
            'user_agent' => $request->userAgent(),
            'latitude' => $locationData['lat'] ?? null,
            'longitude' => $locationData['lon'] ?? null,
            'country' => $locationData['country'] ?? null,
            'city' => $locationData['city'] ?? null,
            'page_visited' => $request->path()
        ]);

        Log::info('Visitor tracked', [
            'ip' => $ip,
            'page' => $request->path(),
            'has_location' => isset($locationData['lat'])
        ]);
    }

    /**
     * Check if should skip tracking
     */
    private function shouldSkipTracking(Request $request): bool
    {
        return $request->is('admin/*') || 
               $request->is('api/*') ||
               $request->is('*.css') ||
               $request->is('*.js') ||
               $request->is('*.png') ||
               $request->is('*.jpg') ||
               $request->is('*.ico') ||
               $request->is('*.svg') ||
               $request->ajax() ||
               $request->expectsJson();
    }

    /**
     * Check if IP is local/private
     */
    private function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1']) ||
               str_starts_with($ip, '192.168.') ||
               str_starts_with($ip, '10.') ||
               str_starts_with($ip, '172.');
    }
}