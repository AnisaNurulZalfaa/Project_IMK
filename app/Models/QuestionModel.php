<?php namespace App\Models;

use CodeIgniter\Model;

class QuestionModel extends Model
{
    protected $table = 'question';
    protected $primaryKey = 'id_question';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['id_test', 'pasangan_kata'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
   

    protected $validationRules = [
        'id_test'       => 'required|is_natural_no_zero',
        'pasangan_kata' => 'required|max_length[100]',
    ];
    protected $skipValidation = false;
}