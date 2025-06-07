<?php namespace App\Models;

use CodeIgniter\Model;

class TestModel extends Model
{
    protected $table = 'test';
    protected $primaryKey = 'id_test';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['nama_test', 'deskripsi'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    
    protected $skipValidation = false;
}