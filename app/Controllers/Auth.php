<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use Firebase\JWT\JWT;

class Auth extends Controller
{
    use ResponseTrait;

    public function register()
    {
        $userModel = new UserModel();

        $data = $this->request->getPost();
        if (empty($data)) {
            $data = $this->request->getJSON(true);
        }

        // Pastikan data tidak kosong
        // Ubah baris ini
        if (empty($data)) {
            return $this->fail(['message' => 'Tidak ada data yang diterima untuk registrasi.'], 400);
        }

        if (!$userModel->validate($data)) {
            $errors = $userModel->errors();
            return $this->failValidationErrors($errors);
        }

        $userModel->save($data);

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Registrasi berhasil! Selamat datang, ' . esc($data['nama']) . '.',
            'data'    => [
                'id_user'    => $userModel->getInsertID(),
                'nama'  => esc($data['nama']),
                'email' => esc($data['email'])
            ]
        ]);
    }

    public function login()
    {
        $userModel = new UserModel();

        // 1. Ambil data dari request
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        if (empty($email) && empty($password)) {
            $json_data = $this->request->getJSON(true);
            $email     = $json_data['email'] ?? null;
            $password  = $json_data['password'] ?? null;
        }

        // 2. Validasi input
        if (empty($email) || empty($password)) {
            return $this->fail(['message' => 'Email dan password harus diisi.'], 400);
        }

        // 3. Cari user
        $user = $userModel->where('email', $email)->first();

        // 4. Verifikasi user dan password
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Email atau password salah.');
        }

        // 5. Login Berhasil! <<== BUAT TOKEN JWT DI SINI ==>>

        try {
            $key = getenv('JWT_SECRET'); // Ambil kunci dari .env
            if (!$key) {
                log_message('error', 'Kunci JWT tidak ditemukan di .env');
                return $this->failServerError('Terjadi kesalahan konfigurasi server.');
            }

            $iat = time(); // Waktu token dibuat (Issued At)
            $exp = $iat + (60 * 60 * 24); // Waktu kadaluarsa (Contoh: 1 hari = 60 detik * 60 menit * 24 jam)
            $iss = base_url(); // Issuer - Siapa yang mengeluarkan token (URL aplikasi Anda)
            $aud = base_url(); // Audience - Untuk siapa token ini (URL aplikasi Anda)

            $payload = [
                'iss'  => $iss,
                'aud'  => $aud,
                'iat'  => $iat,
                'exp'  => $exp,
                'uid'  => $user['id_user'], // Ganti 'id_user' sesuai nama kolom ID Anda
                'email'=> $user['email'],
                // Anda bisa menambahkan data lain (role, nama, dll)
                // HINDARI menyimpan data sensitif di payload!
            ];

            // Membuat token
            $token = JWT::encode($payload, $key, 'HS256'); // HS256 adalah algoritma umum

        } catch (\Exception $e) {
            log_message('error', 'Gagal membuat token JWT: ' . $e->getMessage());
            return $this->failServerError('Gagal memproses login, coba lagi nanti.');
        }


        // Hapus password hash sebelum mengirim respons
        unset($user['password']);

        // 6. Kirim respons sukses DENGAN TOKEN
        return $this->respond([
            'status'  => 200,
            'message' => 'Login berhasil!',
            'data'    => $user,
            'token'   => $token // <--- SERTAKAN TOKEN DI SINI
        ]);
    }
}
