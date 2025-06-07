<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKeywordTable extends Migration
{
   public function up()
    {
        $this->forge->addField([
            'id_keyword' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_question' => [ 
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'kata_kunci' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true, 
            ],
            'skor' => [
                'type'       => 'INT',
                'constraint' => 3, 
                'default'    => 0,
            ],
        ]);

        $this->forge->addKey('id_keyword', true); 

        $this->forge->addKey('id_question'); 
        $this->forge->addForeignKey('id_question', 'question', 'id_question', 'CASCADE', 'CASCADE');

        $this->forge->createTable('keyword');
    }

    public function down()
    {
        $this->forge->dropTable('keyword');
    }
}



