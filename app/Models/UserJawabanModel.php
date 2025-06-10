<?php

namespace App\Models;

use CodeIgniter\Model;

class UserJawabanModel extends Model
{
    protected $table = 'user_jawaban';
    protected $primaryKey = 'id_jawaban';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'id_question',
        'id_user',
        'id_test',
        'id_result',
        'jawaban_user',
        'waktu_submit'
    ];
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';


    protected $validationRules = [
        'id_result'      => 'required|is_natural_no_zero',
        'id_question' => 'required|is_natural_no_zero',
        'jawaban_user' => 'required|max_length[1000]', // Contoh max_length, sesuaikan
    ];
    protected $skipValidation = false;
}
