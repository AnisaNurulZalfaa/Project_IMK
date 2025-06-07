<?php namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id_user';

    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['nama', 'email', 'password']; // Field yang boleh diisi dari input


    // Validation
    protected $validationRules = [
        'nama'     => 'required|min_length[3]|max_length[100]',
        'email'    => 'required|valid_email|is_unique[users.email]', // Email harus valid dan unik di tabel users
        'password' => 'required|min_length[8]', // Password minimal 8 karakter
    ];
    protected $validationMessages = [
        'email' => [
            'is_unique'   => 'Maaf, email ini sudah terdaftar.',
            'valid_email' => 'Format email tidak valid.'
        ],
        'password' => [
            'min_length' => 'Password minimal 8 karakter.'
        ]
    ];
    protected $skipValidation = false;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword']; // Sebelum data dimasukkan, jalankan fungsi hashPassword
    protected $beforeUpdate   = ['hashPassword']; // Sebelum data diupdate, jalankan fungsi hashPassword (jika password berubah)

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            // Menggunakan password_hash untuk hashing yang aman
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }
}