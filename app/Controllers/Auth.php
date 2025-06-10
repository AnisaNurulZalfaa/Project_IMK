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

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        if (empty($email) && empty($password)) {
            $json_data = $this->request->getJSON(true);
            $email     = $json_data['email'] ?? null;
            $password  = $json_data['password'] ?? null;
        }

        if (empty($email) || empty($password)) {
            return $this->fail(['message' => 'Email dan password harus diisi.'], 400);
        }

        $user = $userModel->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Email atau password salah.');
        }
        try {
            $key = getenv('JWT_SECRET'); 
            if (!$key) {
                log_message('error', 'Kunci JWT tidak ditemukan di .env');
                return $this->failServerError('Terjadi kesalahan konfigurasi server.');
            }

            $iat = time(); 
            $exp = $iat + 
            $iss = base_url();
            $aud = base_url(); 

            $payload = [
                'iss'  => $iss,
                'aud'  => $aud,
                'iat'  => $iat,
                'exp'  => $exp,
                'uid'  => $user['id_user'], 
                'email' => $user['email'],
            ];

          
            $token = JWT::encode($payload, $key, 'HS256'); 

        } catch (\Exception $e) {
            log_message('error', 'Gagal membuat token JWT: ' . $e->getMessage());
            return $this->failServerError('Gagal memproses login, coba lagi nanti.');
        }
        unset($user['password']);

        return $this->respond([
            'status'  => 200,
            'message' => 'Login berhasil!',
            'data'    => $user,
            'token'   => $token 
        ]);
    }
}
