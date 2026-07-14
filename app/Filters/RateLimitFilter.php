<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class RateLimitFilter implements FilterInterface
{
    /**
     * Argumen: [nama_bucket, jumlah_maksimal, periode_detik]
     * Contoh: filter:ratelimit:login,5,300 -> maksimal 5 percobaan per 5 menit.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $bucketName = $arguments[0] ?? 'default';
        $maxRequests = (int) ($arguments[1] ?? 10);
        $periodSeconds = (int) ($arguments[2] ?? 60);

        $throttler = Services::throttler();

        // Key unik per-IP per-bucket. IP di-hash dulu (md5) karena IPv6
        // mengandung karakter ':' yang termasuk reserved characters di
        // sistem cache CI4 dan akan menyebabkan InvalidArgumentException.
        $key = $bucketName . '_' . md5($request->getIPAddress());

        if ($throttler->check($key, $maxRequests, $periodSeconds) === false) {
            log_message('warning', "Rate limit exceeded: bucket={$bucketName}, ip=" . $request->getIPAddress());

            $response = Services::response();
            return $response->setStatusCode(429)
                ->setJSON(['message' => 'Terlalu banyak percobaan. Silakan coba lagi beberapa saat.']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request selesai.
    }
}