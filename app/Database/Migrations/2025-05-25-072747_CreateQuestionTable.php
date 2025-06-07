<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuestionTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_question' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_test' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'pasangan_kata' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
        ]);
        $this->forge->addKey('id_question', true);
        $this->forge->addKey('id_test');
        $this->forge->addForeignKey('id_test', 'test', 'id_test', 'CASCADE', 'CASCADE');
        $this->forge->createTable('question');
    }

    public function down()
    {
        $this->forge->dropTable('question');
    }
}
