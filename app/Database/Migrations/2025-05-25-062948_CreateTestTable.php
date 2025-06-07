<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTestTable extends Migration
{
     public function up()
    {
        $this->forge->addField([
            'id_test' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_test' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'deskripsi' => [
                'type'       => 'TEXT',
                'null'       => true, 
            ],
        ]);
        $this->forge->addKey('id_test', true); 
        $this->forge->createTable('test'); 
    }

    public function down()
    {
        $this->forge->dropTable('test');
    }
}



