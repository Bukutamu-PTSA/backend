<?php

namespace App\Http\Middleware;

use App\Models\DataVisitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VisitorMiddleware
{
    /**
     * Rekam metrik kunjungan untuk route publik ke tabel data_visitors.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya catat permintaan GET yang berhasil agar tidak membebani tabel
        // dengan preflight, aksi tulis, atau error.
        if ($request->isMethod('GET')) {
            $this->recordVisit($request);
        }

        return $response;
    }

    private function recordVisit(Request $request): void
    {
        try {
            $agent = (string) $request->userAgent();
            $now = now();

            DataVisitor::create([
                'ip_address' => $request->ip(),
                'page_visited' => $request->path(),
                'visit_date' => $now->toDateString(),
                'visit_time' => $now->toTimeString(),
                'user_agent' => $agent !== '' ? $agent : null,
                'referrer' => $request->headers->get('referer'),
                'device_type' => $this->detectDevice($agent),
                'browser_type' => $this->detectBrowser($agent),
            ]);
        } catch (\Throwable $e) {
            // Pencatatan pengunjung tidak boleh mengganggu response utama.
            Log::warning('Gagal mencatat data pengunjung: '.$e->getMessage());
        }
    }

    private function detectDevice(string $agent): string
    {
        $agent = strtolower($agent);

        return match (true) {
            str_contains($agent, 'tablet') || str_contains($agent, 'ipad') => 'tablet',
            str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone') => 'mobile',
            $agent === '' => 'unknown',
            default => 'desktop',
        };
    }

    private function detectBrowser(string $agent): string
    {
        $agent = strtolower($agent);

        return match (true) {
            str_contains($agent, 'edg') => 'Edge',
            str_contains($agent, 'opr') || str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'chrome') => 'Chrome',
            str_contains($agent, 'firefox') => 'Firefox',
            str_contains($agent, 'safari') => 'Safari',
            default => 'Other',
        };
    }
}
