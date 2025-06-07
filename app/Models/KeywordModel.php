<?php 
namespace App\Models;

use CodeIgniter\Model;

class KeywordModel extends Model
{
    protected $table = 'keyword';
    protected $primaryKey = 'id_keyword';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['id_question', 'kata_kunci', 'skor'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
   

    protected $validationRules = [
        'id_question' => 'required|is_natural_no_zero',
        'kata_kunci'  => 'required|max_length[100]',
        'skor'        => 'required|is_natural',
    ];
    protected $skipValidation = false;
}