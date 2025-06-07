<?php

namespace App\Models;

use CodeIgniter\Model;

class TestResultModel extends Model
{
    protected $table = 'test_result';
    protected $primaryKey = 'id_result';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'id_user',
        'id_test',
        'pendidikan',
        'tujuan_pemeriksaan',
        'tanggal_pengerjaan',
        'skor_total',
        'interpretasi'
    ];
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';


    protected $validationRules = [
        'id_user'      => 'required|is_natural_no_zero',
        'id_test'      => 'required|is_natural_no_zero',
        'pendidikan'         => 'required|string|min_length[2]',
        'tujuan_pemeriksaan' => 'required|string|min_length[5]',
        'skor_total'   => 'required|integer',
        'tanggal_pengerjaan'      => 'required|valid_date',
    ];
    protected $skipValidation = false;
}
