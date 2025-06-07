<?php

namespace App\Filters;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTAuthFilter implements FilterInterface
{
    use ResponseTrait;

    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        $token = null;

        // Cek apakah header Authorization ada
        if (!empty($header)) {
            // Header harus dalam format "Bearer <token>"
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        // Jika token tidak ada, kirim respons "Unauthorized"
        if (is_null($token) || empty($token)) {
            return Services::response()
                ->setJSON(['message' => 'Token otentikasi tidak ditemukan.'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        try {
            // Coba decode token
            $key = getenv('JWT_SECRET');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Anda bisa menyimpan data user yang sudah di-decode ke dalam Request
            // agar bisa diakses di Controller nanti.
            
            $request->user = $decoded;

        } catch (\Exception $e) {
            // Jika token tidak valid (kadaluarsa, signature salah, dll)
            return Services::response()
                ->setJSON([
                    'message' => 'Token tidak valid atau kadaluarsa.',
                    'error' => $e->getMessage()
                ])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        // Jika token valid, lanjutkan ke controller
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu melakukan apa-apa setelah request
    }
}